<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

/**
 * Main calendar view for local_calendarcategories.
 *
 * Supports three views: month (default), week, list.
 * Fully responsive; on mobile the sidebar moves to an off-canvas panel
 * and a bottom navigation bar replaces the toolbar view-toggle.
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/calendarcategories:view', $context);

// ── Parameters ──────────────────────────────────────────────────────────────
$view  = optional_param('view',  'month', PARAM_ALPHA);   // month | week | list
$year  = optional_param('year',  (int)date('Y'),  PARAM_INT);
$month = optional_param('month', (int)date('n'),  PARAM_INT);
$week  = optional_param('week',  (int)date('W'),  PARAM_INT);

// Clamp values.
$month = max(1, min(12, $month));
$year  = max(2000, min(2099, $year));
$view  = in_array($view, ['month', 'week', 'list']) ? $view : 'month';

// ── Page setup ───────────────────────────────────────────────────────────────
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/calendarcategories/view.php', [
    'view'  => $view,
    'year'  => $year,
    'month' => $month,
]));
$PAGE->set_title(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_heading(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_pagelayout('standard');

// CSS.
$PAGE->requires->css('/local/calendarcategories/styles/calendarcategories.css');
// Bootstrap Icons via CDN (Moodle 5 uses BS5; icons may not be bundled).
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'));

// ── Data ────────────────────────────────────────────────────────────────────
$categories  = \local_calendarcategories\category_manager::get_visible_categories();
$monthevents = \local_calendarcategories\event_manager::get_month_events($year, $month);
$upcoming    = ($view === 'list')
    ? \local_calendarcategories\event_manager::get_upcoming_for_user(180)
    : [];

// Group month events by date string for quick lookup.
$evbydate = [];
foreach ($monthevents as $ev) {
    $d = date('Y-m-d', $ev->timestart);
    $evbydate[$d][] = $ev;
}

// ── Navigation helpers ───────────────────────────────────────────────────────
$prevmonth = $month === 1  ? 12 : $month - 1;
$prevyear  = $month === 1  ? $year - 1 : $year;
$nextmonth = $month === 12 ? 1  : $month + 1;
$nextyear  = $month === 12 ? $year + 1 : $year;

$prevurl = new moodle_url('/local/calendarcategories/view.php', ['view' => $view, 'year' => $prevyear, 'month' => $prevmonth]);
$nexturl = new moodle_url('/local/calendarcategories/view.php', ['view' => $view, 'year' => $nextyear,  'month' => $nextmonth]);
$todayurl = new moodle_url('/local/calendarcategories/view.php', ['view' => $view, 'year' => date('Y'), 'month' => date('n')]);
$addeventurl = new moodle_url('/local/calendarcategories/addevent.php');

// Monatsnamen: direkt als deutsche/englische Strings in Plugin-Sprachdatei.
$monthnames = [
    1  => get_string('month_jan', 'local_calendarcategories'),
    2  => get_string('month_feb', 'local_calendarcategories'),
    3  => get_string('month_mar', 'local_calendarcategories'),
    4  => get_string('month_apr', 'local_calendarcategories'),
    5  => get_string('month_may', 'local_calendarcategories'),
    6  => get_string('month_jun', 'local_calendarcategories'),
    7  => get_string('month_jul', 'local_calendarcategories'),
    8  => get_string('month_aug', 'local_calendarcategories'),
    9  => get_string('month_sep', 'local_calendarcategories'),
    10 => get_string('month_oct', 'local_calendarcategories'),
    11 => get_string('month_nov', 'local_calendarcategories'),
    12 => get_string('month_dec', 'local_calendarcategories'),
];

// ── Can the user create events? ───────────────────────────────────────────────
$canadd = has_capability('local/calendarcategories:addevent', $context) && !empty($categories);

// ── Output ───────────────────────────────────────────────────────────────────
echo $OUTPUT->header();

?>
<div class="lcc-page" id="lcc-page">

  <?php /* ── Toolbar ─────────────────────────────────────────── */ ?>
  <div class="lcc-toolbar">
    <div class="lcc-toolbar-left">

      <?php /* Mobile: hamburger to open category sidebar */ ?>
      <button class="btn btn-sm btn-outline-secondary d-md-none"
              type="button" data-bs-toggle="offcanvas" data-bs-target="#lccCatOffcanvas"
              aria-controls="lccCatOffcanvas" aria-label="<?php echo get_string('showcategories', 'local_calendarcategories'); ?>">
        <i class="bi bi-list" aria-hidden="true"></i>
      </button>

      <a href="<?php echo $prevurl; ?>" class="btn btn-sm btn-outline-secondary" aria-label="<?php echo get_string('previousmonth', 'local_calendarcategories'); ?>">
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
      </a>
      <span class="lcc-month-label fw-semibold"><?php echo $monthnames[$month] . ' ' . $year; ?></span>
      <a href="<?php echo $nexturl; ?>" class="btn btn-sm btn-outline-secondary" aria-label="<?php echo get_string('nextmonth', 'local_calendarcategories'); ?>">
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
      </a>
      <a href="<?php echo $todayurl; ?>" class="btn btn-sm btn-outline-secondary">
        <?php echo get_string('today', 'local_calendarcategories'); ?>
      </a>
    </div>
    <div class="lcc-toolbar-right">

      <?php /* Desktop: view toggle */ ?>
      <div class="btn-group lcc-view-toggle" role="group" aria-label="<?php echo get_string('calendarview', 'local_calendarcategories'); ?>">
        <?php foreach (['list' => get_string('viewlist', 'local_calendarcategories'),
                         'month'=> get_string('viewmonth', 'local_calendarcategories'),
                         'week' => get_string('viewweek', 'local_calendarcategories')] as $v => $label): ?>
        <a href="<?php echo (new moodle_url('/local/calendarcategories/view.php', ['view' => $v, 'year' => $year, 'month' => $month]))->out(); ?>"
           class="btn btn-sm btn-outline-secondary<?php echo $view === $v ? ' active' : ''; ?>"
           aria-current="<?php echo $view === $v ? 'true' : 'false'; ?>">
          <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
      </div>

      <?php if ($canadd): ?>
      <a href="<?php echo $addeventurl; ?>" class="btn btn-sm btn-primary d-none d-md-inline-flex align-items-center gap-1">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        <?php echo get_string('addevent', 'local_calendarcategories'); ?>
      </a>
      <?php endif; ?>
    </div>
  </div><!-- /.lcc-toolbar -->

  <?php /* ── Off-canvas sidebar (mobile) ────────────────────── */ ?>
  <div class="offcanvas offcanvas-start lcc-offcanvas-cats" tabindex="-1" id="lccCatOffcanvas"
       aria-labelledby="lccCatOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="lccCatOffcanvasLabel">
        <?php echo get_string('mycategories', 'local_calendarcategories'); ?>
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo get_string('close', 'form'); ?>"></button>
    </div>
    <div class="offcanvas-body">
      <?php echo self_render_cat_list($categories, true); ?>
    </div>
  </div>

  <?php /* ── Body: sidebar + main ──────────────────────────────── */ ?>
  <div class="lcc-body">

    <?php /* Desktop sidebar */ ?>
    <aside class="lcc-sidebar d-none d-md-block" aria-label="<?php echo get_string('mycategories', 'local_calendarcategories'); ?>">
      <div class="lcc-sidebar-section">
        <h6><?php echo get_string('mycategories', 'local_calendarcategories'); ?></h6>
        <?php echo self_render_cat_list($categories, false); ?>
      </div>
      <div class="lcc-sidebar-section">
        <p class="small text-muted mb-0"><?php echo get_string('categoryhint', 'local_calendarcategories'); ?></p>
      </div>
    </aside>

    <?php /* Main content area */ ?>
    <main class="lcc-main" id="lcc-main">

      <?php if ($view === 'month'): ?>
        <?php echo render_month_view($year, $month, $evbydate, $canadd, $addeventurl); ?>

      <?php elseif ($view === 'week'): ?>
        <?php echo render_week_view($year, $month, $monthevents, $canadd, $addeventurl); ?>

      <?php else: /* list */ ?>
        <?php echo render_list_view($upcoming, $canadd, $addeventurl); ?>
      <?php endif; ?>

    </main>
  </div><!-- /.lcc-body -->

</div><!-- /.lcc-page -->

<?php /* ── FAB (mobile, floating action button) ──────────────── */ ?>
<?php if ($canadd): ?>
<a href="<?php echo $addeventurl; ?>" class="lcc-fab d-md-none" aria-label="<?php echo get_string('addevent', 'local_calendarcategories'); ?>">
  <i class="bi bi-plus-lg" aria-hidden="true"></i>
</a>
<?php endif; ?>

<?php /* ── Mobile bottom nav ────────────────────────────────────── */ ?>
<nav class="lcc-mobile-nav" aria-label="<?php echo get_string('calendarview', 'local_calendarcategories'); ?>">
  <div class="lcc-mobile-nav-inner">
    <?php
    $mobilenav = [
        'list'  => ['bi-list-ul',    get_string('viewlist',  'local_calendarcategories')],
        'month' => ['bi-calendar3',  get_string('viewmonth', 'local_calendarcategories')],
        'week'  => ['bi-calendar-week', get_string('viewweek', 'local_calendarcategories')],
    ];
    foreach ($mobilenav as $v => [$icon, $label]):
        $url = (new moodle_url('/local/calendarcategories/view.php', ['view' => $v, 'year' => $year, 'month' => $month]))->out();
    ?>
    <a href="<?php echo $url; ?>"
       class="lcc-mobile-nav-btn<?php echo $view === $v ? ' active' : ''; ?>"
       aria-current="<?php echo $view === $v ? 'page' : 'false'; ?>">
      <i class="bi <?php echo $icon; ?>" aria-hidden="true"></i>
      <?php echo $label; ?>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<?php
// ── Helper functions ──────────────────────────────────────────────────────────

/**
 * Render the category list (sidebar or off-canvas).
 */
function self_render_cat_list(array $categories, bool $mobile): string {
    if (empty($categories)) {
        return html_writer::tag('p', get_string('nocategories', 'local_calendarcategories'), ['class' => 'small text-muted']);
    }
    $out = '';
    foreach ($categories as $cat) {
        $dot = html_writer::span('', 'lcc-cat-dot', ['style' => 'background:' . s($cat->color)]);
        $out .= html_writer::tag('div',
            $dot . html_writer::span(format_string($cat->name)),
            ['class' => 'lcc-cat-item active', 'title' => format_string($cat->name)]
        );
    }
    return $out;
}

/**
 * Render the month calendar grid.
 */
function render_month_view(int $year, int $month, array $evbydate, bool $canadd, moodle_url $addeventurl): string {
    $daynames = [
        get_string('day_mon', 'local_calendarcategories'),
        get_string('day_tue', 'local_calendarcategories'),
        get_string('day_wed', 'local_calendarcategories'),
        get_string('day_thu', 'local_calendarcategories'),
        get_string('day_fri', 'local_calendarcategories'),
        get_string('day_sat', 'local_calendarcategories'),
        get_string('day_sun', 'local_calendarcategories'),
    ];

    $out = '<div class="lcc-cal-header" role="row">';
    foreach ($daynames as $d) {
        $out .= '<div role="columnheader" aria-label="' . s($d) . '">' . substr($d, 0, 2) . '</div>';
    }
    $out .= '</div>';

    $out .= '<div class="lcc-cal-grid" role="grid">';

    $first   = mktime(0, 0, 0, $month, 1, $year);
    $dow     = (int)date('N', $first) - 1; // 0=Mon … 6=Sun
    $today   = date('Y-m-d');
    $daysInMonth = (int)date('t', $first);

    // Total cells: always 6 weeks so layout stays stable.
    for ($cell = 0; $cell < 42; $cell++) {
        $dayOffset = $cell - $dow;
        $cellDate  = date('Y-m-d', mktime(0, 0, 0, $month, 1 + $dayOffset, $year));
        $dayNum    = (int)date('j', strtotime($cellDate));
        $cellMonth = (int)date('n', strtotime($cellDate));
        $isThisMonth = ($cellMonth === $month);
        $isToday     = ($cellDate === $today);

        $classes = 'lcc-day' . ($isThisMonth ? '' : ' other-month') . ($isToday ? ' today' : '');

        $numEl  = html_writer::div((string)$dayNum, 'lcc-day-num', ['aria-label' => $cellDate]);

        $pillsHtml = '';
        $dayevents = $evbydate[$cellDate] ?? [];
        $shown = 0;
        foreach ($dayevents as $ev) {
            if ($shown >= 3) {
                $more = count($dayevents) - 3;
                $pillsHtml .= html_writer::div(
                    '+' . $more . ' ' . get_string('moreevents', 'local_calendarcategories'),
                    'lcc-more'
                );
                break;
            }
            $color = s($ev->categorycolor);
            $time  = html_writer::span(date('H:i', $ev->timestart), 'lcc-pill-time');
            $pillUrl = new moodle_url('/local/calendarcategories/view.php', ['event' => $ev->id]);
            $pill = html_writer::tag('a',
                $time . ' ' . format_string($ev->name),
                [
                    'class'  => 'lcc-pill',
                    'href'   => $pillUrl->out(),
                    'style'  => "background:{$color}22;color:{$color};border-left:3px solid {$color}",
                    'title'  => format_string($ev->name),
                ]
            );
            $pillsHtml .= $pill;
            $shown++;
        }

        // Click empty cell → pre-fill date in add form.
        $cellLink = '';
        if ($canadd && $isThisMonth) {
            $addurl = new moodle_url('/local/calendarcategories/addevent.php', ['date' => $cellDate]);
            $cellLink = ' data-addurl="' . $addurl->out() . '"';
        }

        $out .= '<div class="' . $classes . '" role="gridcell" aria-label="' . $cellDate . '"' . $cellLink . '>'
              . $numEl . $pillsHtml . '</div>';
    }

    $out .= '</div>'; // .lcc-cal-grid

    // JS: clicking empty cell area navigates to addevent with pre-filled date.
    $out .= '<script>
(function(){
  document.querySelectorAll(".lcc-day[data-addurl]").forEach(function(cell){
    cell.addEventListener("click", function(e){
      if (!e.target.closest("a")) {
        window.location.href = cell.dataset.addurl;
      }
    });
  });
})();
</script>';

    return $out;
}

/**
 * Render the week view (time-grid, Mon–Sun of the week containing the 1st of the month).
 */
function render_week_view(int $year, int $month, array $events, bool $canadd, moodle_url $addeventurl): string {
    // Find the Monday of the current week (week containing today if in this month).
    $todayTs  = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
    $monthTs  = mktime(0, 0, 0, $month, 1, $year);
    $refTs    = (date('n', $todayTs) == $month && date('Y', $todayTs) == $year) ? $todayTs : $monthTs;
    $dow      = (int)date('N', $refTs); // 1=Mon
    $monday   = $refTs - ($dow - 1) * DAYSECS;

    $out  = '<div class="lcc-week-header">';
    $out .= '<div></div>'; // corner
    for ($d = 0; $d < 7; $d++) {
        $ts   = $monday + $d * DAYSECS;
        $isToday = date('Y-m-d', $ts) === date('Y-m-d');
        $numCls = 'lcc-day-num' . ($isToday ? ' bg-primary text-white rounded-circle' : '');
        $out .= '<div class="lcc-week-header-day">'
              . '<span>' . date('D', $ts) . '</span>'
              . '<div class="' . $numCls . '" style="margin:0 auto">' . (int)date('j', $ts) . '</div>'
              . '</div>';
    }
    $out .= '</div>';

    $out .= '<div style="overflow-y:auto;flex:1"><div class="lcc-week-grid">';
    $hours = range(6, 22);
    foreach ($hours as $hour) {
        $out .= '<div class="lcc-hour-label">' . sprintf('%02d:00', $hour) . '</div>';
        for ($d = 0; $d < 7; $d++) {
            $ts   = $monday + $d * DAYSECS + $hour * 3600;
            $tsEnd = $ts + 3600;
            // Events starting in this hour slot.
            $slotEvents = array_filter($events, fn($e) => $e->timestart >= $ts && $e->timestart < $tsEnd);
            $inner = '';
            foreach ($slotEvents as $ev) {
                $color = s($ev->categorycolor);
                $inner .= html_writer::tag('a',
                    '<span style="font-size:.65rem;opacity:.85">' . date('H:i', $ev->timestart) . '</span> '
                    . format_string($ev->name),
                    [
                        'href'  => (new moodle_url('/local/calendarcategories/view.php', ['event' => $ev->id]))->out(),
                        'class' => 'lcc-pill d-block mb-1',
                        'style' => "background:{$color}22;color:{$color};border-left:3px solid {$color};white-space:normal",
                    ]
                );
            }
            $out .= '<div class="lcc-week-col">' . $inner . '</div>';
        }
    }
    $out .= '</div></div>'; // week-grid + wrapper

    return $out;
}

/**
 * Render the list view (upcoming events grouped by month).
 */
function render_list_view(array $events, bool $canadd, moodle_url $addeventurl): string {
    if (empty($events)) {
        return '<div class="lcc-list-empty">'
             . '<i class="bi bi-calendar-x" style="font-size:2rem;opacity:.3" aria-hidden="true"></i>'
             . '<p>' . get_string('noupcoming', 'local_calendarcategories') . '</p>'
             . ($canadd
                 ? '<a href="' . $addeventurl->out() . '" class="btn btn-sm btn-primary">'
                   . get_string('addevent', 'local_calendarcategories') . '</a>'
                 : '')
             . '</div>';
    }

    $out     = '<div class="lcc-list">';
    $curmon  = '';

    foreach ($events as $ev) {
        $mon = get_string('month_' . strtolower(date('M', $ev->timestart)), 'local_calendarcategories') . ' ' . date('Y', $ev->timestart);
        if ($mon !== $curmon) {
            $curmon = $mon;
            $out .= '<div class="lcc-list-month">' . s($mon) . '</div>';
        }
        $color  = s($ev->categorycolor);
        $dow    = get_string('day_' . strtolower(date('D', $ev->timestart)), 'local_calendarcategories');
        $daynum = date('j', $ev->timestart);
        $time   = date('H:i', $ev->timestart);

        $out .= html_writer::tag('a',
            '<div class="lcc-list-date"><div class="day">' . $daynum . '</div><div class="dow">' . $dow . '</div></div>'
            . '<div class="lcc-list-bar" style="background:' . $color . '"></div>'
            . '<div class="lcc-list-info">'
            . '<div class="lcc-list-title">' . format_string($ev->name) . '</div>'
            . '<div class="lcc-list-meta">' . $time
            . (!empty($ev->location) ? ' · ' . s($ev->location) : '')
            . ' · <span style="color:' . $color . ';font-weight:500">' . format_string($ev->categoryname) . '</span>'
            . '</div></div>',
            [
                'href'  => (new moodle_url('/local/calendarcategories/view.php', ['event' => $ev->id]))->out(),
                'class' => 'lcc-list-event text-decoration-none text-body',
            ]
        );
    }
    $out .= '</div>';
    return $out;
}

echo $OUTPUT->footer();
