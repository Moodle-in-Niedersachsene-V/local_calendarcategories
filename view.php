<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Main calendar view for local_calendarcategories.
 *
 * Supports three views: month (default), week, list.
 * Fully responsive; on mobile the sidebar moves to an off-canvas panel
 * and a bottom navigation bar replaces the toolbar view-toggle.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_calendarcategories\output\renderer;
use local_calendarcategories\category_manager;
use local_calendarcategories\event_manager;

require_login();
$context = context_system::instance();
require_capability('local/calendarcategories:view', $context);

// Parameters.
$view  = optional_param('view', 'month', PARAM_ALPHA);
$year  = optional_param('year', (int)date('Y'), PARAM_INT);
$month = optional_param('month', (int)date('n'), PARAM_INT);

// Clamp values.
$month = max(1, min(12, $month));
$year  = max(2000, min(2099, $year));
$view  = in_array($view, ['month', 'week', 'list']) ? $view : 'month';

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/calendarcategories/view.php', [
    'view'  => $view,
    'year'  => $year,
    'month' => $month,
]));
$PAGE->set_title(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_heading(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/local/calendarcategories/styles/calendarcategories.css');
$PAGE->requires->css(new moodle_url(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'
));

// Data.
$categories   = category_manager::get_visible_categories();
$monthEvents = event_manager::get_month_events($year, $month);
$upcoming     = ($view === 'list') ? event_manager::get_upcoming_for_user(180) : [];

// Group month events by date string for quick lookup.
$evByDate = [];
foreach ($monthEvents as $ev) {
    $d = date('Y-m-d', $ev->timestart);
    $evByDate[$d][] = $ev;
}

// Navigation URLs.
$prevMonth    = $month === 1 ? 12 : $month - 1;
$prevYear     = $month === 1 ? $year - 1 : $year;
$nextMonth    = $month === 12 ? 1 : $month + 1;
$nextYear     = $month === 12 ? $year + 1 : $year;
$prevUrl      = new moodle_url(
    '/local/calendarcategories/view.php',
    ['view' => $view, 'year' => $prevYear, 'month' => $prevMonth]
);
$nextUrl      = new moodle_url(
    '/local/calendarcategories/view.php',
    ['view' => $view, 'year' => $nextYear, 'month' => $nextMonth]
);
$todayUrl     = new moodle_url(
    '/local/calendarcategories/view.php',
    ['view' => $view, 'year' => date('Y'), 'month' => date('n')]
);
$addeventUrl  = new moodle_url('/local/calendarcategories/addevent.php');

// Month label.
$monthNames = [
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

$canAdd = has_capability('local/calendarcategories:addevent', $context) && !empty($categories);

echo $OUTPUT->header();

// Toolbar.
echo html_writer::start_div('lcc-toolbar');
echo html_writer::start_div('lcc-toolbar-left');

echo html_writer::tag('button', '<i class="bi bi-list" aria-hidden="true"></i>', [
    'class'            => 'btn btn-sm btn-outline-secondary d-md-none',
    'type'             => 'button',
    'data-bs-toggle'   => 'offcanvas',
    'data-bs-target'   => '#lccCatOffcanvas',
    'aria-controls'    => 'lccCatOffcanvas',
    'aria-label'       => get_string('showcategories', 'local_calendarcategories'),
]);

echo html_writer::tag('a', '<i class="bi bi-chevron-left" aria-hidden="true"></i>', [
    'href'       => $prevUrl->out(),
    'class'      => 'btn btn-sm btn-outline-secondary',
    'aria-label' => get_string('previousmonth', 'local_calendarcategories'),
]);
echo html_writer::span($monthNames[$month] . ' ' . $year, 'lcc-month-label fw-semibold');
echo html_writer::tag('a', '<i class="bi bi-chevron-right" aria-hidden="true"></i>', [
    'href'       => $nextUrl->out(),
    'class'      => 'btn btn-sm btn-outline-secondary',
    'aria-label' => get_string('nextmonth', 'local_calendarcategories'),
]);
echo html_writer::tag('a', get_string('today', 'local_calendarcategories'), [
    'href'  => $todayUrl->out(),
    'class' => 'btn btn-sm btn-outline-secondary',
]);
echo html_writer::end_div(); // Toolbar-left.

echo html_writer::start_div('lcc-toolbar-right');

// Desktop view toggle.
echo html_writer::start_tag('div', ['class' => 'btn-group lcc-view-toggle', 'role' => 'group']);
foreach (['list' => 'viewlist', 'month' => 'viewmonth', 'week' => 'viewweek'] as $v => $strkey) {
    echo html_writer::tag('a', get_string($strkey, 'local_calendarcategories'), [
        'href'         => (new moodle_url(
            '/local/calendarcategories/view.php',
            ['view' => $v, 'year' => $year, 'month' => $month]
        ))->out(),
        'class'        => 'btn btn-sm btn-outline-secondary' . ($view === $v ? ' active' : ''),
        'aria-current' => $view === $v ? 'true' : 'false',
    ]);
}
echo html_writer::end_tag('div');

if ($canAdd) {
    echo html_writer::tag(
        'a',
        '<i class="bi bi-plus-lg" aria-hidden="true"></i> '
        . get_string('addevent', 'local_calendarcategories'),
        [
            'href'  => $addeventUrl->out(),
            'class' => 'btn btn-sm btn-primary d-none d-md-inline-flex align-items-center gap-1',
        ]
    );
}
echo html_writer::end_div(); // Toolbar-right.
echo html_writer::end_div(); // Toolbar.

// Off-canvas sidebar (mobile).
echo html_writer::start_tag('div', [
    'class'               => 'offcanvas offcanvas-start lcc-offcanvas-cats',
    'tabindex'            => '-1',
    'id'                  => 'lccCatOffcanvas',
    'aria-labelledby'     => 'lccCatOffcanvasLabel',
]);
echo html_writer::start_div('offcanvas-header');
echo html_writer::tag(
    'h5',
    get_string('mycategories', 'local_calendarcategories'),
    ['class' => 'offcanvas-title', 'id' => 'lccCatOffcanvasLabel']
);
echo html_writer::tag('button', '', [
    'type'             => 'button',
    'class'            => 'btn-close',
    'data-bs-dismiss'  => 'offcanvas',
    'aria-label'       => get_string('close', 'form'),
]);
echo html_writer::end_div();
echo html_writer::start_div('offcanvas-body');
echo renderer::render_cat_list($categories);
echo html_writer::end_div();
echo html_writer::end_tag('div');

// Body: sidebar + main.
echo html_writer::start_div('lcc-body');

// Desktop sidebar.
echo html_writer::start_tag('aside', [
    'class'      => 'lcc-sidebar d-none d-md-block',
    'aria-label' => get_string('mycategories', 'local_calendarcategories'),
]);
echo html_writer::start_div('lcc-sidebar-section');
echo html_writer::tag('h6', get_string('mycategories', 'local_calendarcategories'));
echo renderer::render_cat_list($categories);
echo html_writer::end_div();
echo html_writer::start_div('lcc-sidebar-section');
echo html_writer::tag(
    'p',
    get_string('categoryhint', 'local_calendarcategories'),
    ['class' => 'small text-muted mb-0']
);
echo html_writer::end_div();
echo html_writer::end_tag('aside');

// Main content.
echo html_writer::start_tag('main', ['class' => 'lcc-main', 'id' => 'lcc-main']);
if ($view === 'month') {
    echo renderer::render_month_view($year, $month, $evByDate, $canAdd, $addeventUrl);
} else if ($view === 'week') {
    echo renderer::render_week_view($year, $month, $monthEvents, $canAdd, $addeventUrl);
} else {
    echo renderer::render_list_view($upcoming, $canAdd, $addeventUrl);
}
echo html_writer::end_tag('main');
echo html_writer::end_div(); // Body.

// FAB (mobile).
if ($canAdd) {
    echo html_writer::tag('a', '<i class="bi bi-plus-lg" aria-hidden="true"></i>', [
        'href'       => $addeventUrl->out(),
        'class'      => 'lcc-fab d-md-none',
        'aria-label' => get_string('addevent', 'local_calendarcategories'),
    ]);
}

// Mobile bottom nav.
echo html_writer::start_tag('nav', [
    'class'      => 'lcc-mobile-nav',
    'aria-label' => get_string('calendarview', 'local_calendarcategories'),
]);
echo html_writer::start_div('lcc-mobile-nav-inner');
$mobileNav = [
    'list'  => ['bi-list-ul', 'viewlist'],
    'month' => ['bi-calendar3', 'viewmonth'],
    'week'  => ['bi-calendar-week', 'viewweek'],
];
foreach ($mobileNav as $v => [$icon, $strkey]) {
    $url = (new moodle_url(
        '/local/calendarcategories/view.php',
        ['view' => $v, 'year' => $year, 'month' => $month]
    ))->out();
    echo html_writer::tag(
        'a',
        '<i class="bi ' . $icon . '" aria-hidden="true"></i>'
        . get_string($strkey, 'local_calendarcategories'),
        [
            'href'         => $url,
            'class'        => 'lcc-mobile-nav-btn' . ($view === $v ? ' active' : ''),
            'aria-current' => $view === $v ? 'page' : 'false',
        ]
    );
}
echo html_writer::end_div();
echo html_writer::end_tag('nav');

echo $OUTPUT->footer();
