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

namespace local_calendarcategories\output;

use html_writer;

/**
 * Renderer for local_calendarcategories views.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer {
    /**
     * Render the category list for sidebar or off-canvas.
     *
     * @param array $categories List of category records.
     * @return string HTML output.
     */
    public static function render_cat_list(array $categories): string {
        if (empty($categories)) {
            return html_writer::tag(
                'p',
                get_string('nocategories', 'local_calendarcategories'),
                ['class' => 'small text-muted']
            );
        }
        $out = '';
        foreach ($categories as $cat) {
            $dot = html_writer::span('', 'lcc-cat-dot', ['style' => 'background:' . s($cat->color)]);
            $out .= html_writer::tag(
                'div',
                $dot . html_writer::span(format_string($cat->name)),
                ['class' => 'lcc-cat-item active', 'title' => format_string($cat->name)]
            );
        }
        return $out;
    }

    /**
     * Render the month calendar grid.
     *
     * @param int   $year     Current year.
     * @param int   $month    Current month (1-12).
     * @param array $evbydate Events keyed by date string YYYY-MM-DD.
     * @param bool  $canadd   Whether the user can create events.
     * @return string HTML output.
     */
    public static function render_month_view(
        int $year,
        int $month,
        array $evbydate,
        bool $canadd
    ): string {
        $daynames = [
            get_string('day_mon', 'local_calendarcategories'),
            get_string('day_tue', 'local_calendarcategories'),
            get_string('day_wed', 'local_calendarcategories'),
            get_string('day_thu', 'local_calendarcategories'),
            get_string('day_fri', 'local_calendarcategories'),
            get_string('day_sat', 'local_calendarcategories'),
            get_string('day_sun', 'local_calendarcategories'),
        ];

        $out = html_writer::start_tag('div', ['class' => 'lcc-cal-header', 'role' => 'row']);
        foreach ($daynames as $d) {
            $out .= '<div role="columnheader" aria-label="' . s($d) . '">' . substr($d, 0, 2) . '</div>';
        }
        $out .= html_writer::end_tag('div');
        $out .= html_writer::start_tag('div', ['class' => 'lcc-cal-grid', 'role' => 'grid']);

        $first = mktime(0, 0, 0, $month, 1, $year);
        $dow   = (int)date('N', $first) - 1;
        $today = date('Y-m-d');

        for ($cell = 0; $cell < 42; $cell++) {
            $dayoffset    = $cell - $dow;
            $celldate     = date('Y-m-d', mktime(0, 0, 0, $month, 1 + $dayoffset, $year));
            $daynum       = (int)date('j', strtotime($celldate));
            $cellmonth    = (int)date('n', strtotime($celldate));
            $isthismonth = ($cellmonth === $month);
            $istoday      = ($celldate === $today);

            $classes = 'lcc-day'
                . ($isthismonth ? '' : ' other-month')
                . ($istoday ? ' today' : '');
            $numel = html_writer::div((string)$daynum, 'lcc-day-num', ['aria-label' => $celldate]);

            $pillshtml = '';
            $dayevents = $evbydate[$celldate] ?? [];
            $shown = 0;
            foreach ($dayevents as $ev) {
                if ($shown >= 3) {
                    $more = count($dayevents) - 3;
                    $pillshtml .= html_writer::div(
                        '+' . $more . ' ' . get_string('moreevents', 'local_calendarcategories'),
                        'lcc-more'
                    );
                    break;
                }
                $color    = s($ev->categorycolor);
                $time     = html_writer::span(date('H:i', $ev->timestart), 'lcc-pill-time');
                $pillurl = new \moodle_url('/local/calendarcategories/view.php', ['event' => $ev->id]);
                $pillshtml .= html_writer::tag(
                    'a',
                    $time . ' ' . format_string($ev->name),
                    [
                        'class' => 'lcc-pill',
                        'href'  => $pillurl->out(),
                        'style' => "background:{$color}22;color:{$color};border-left:3px solid {$color}",
                        'title' => format_string($ev->name),
                    ]
                );
                $shown++;
            }

            $celllink = '';
            if ($canadd && $isthismonth) {
                $addurl   = new \moodle_url('/local/calendarcategories/addevent.php', ['date' => $celldate]);
                $celllink = ' data-addurl="' . $addurl->out() . '"';
            }

            $out .= '<div class="' . $classes . '" role="gridcell"'
                . ' aria-label="' . $celldate . '"' . $celllink . '>'
                . $numel . $pillshtml . '</div>';
        }
        $out .= '</div>';
        $js = 'document.querySelectorAll(".lcc-day[data-addurl]").forEach(function(cell){'
            . 'cell.addEventListener("click",function(e){'
            . 'if(!e.target.closest("a")){window.location.href=cell.dataset.addurl;}'
            . '});});';
        $out .= html_writer::end_tag('div');
        $out .= html_writer::tag('script', $js);

        return $out;
    }

    /**
     * Render the week time-grid view.
     *
     * @param int   $year   Current year.
     * @param int   $month  Current month (1-12).
     * @param array $events Events for the month.
     * @return string HTML output.
     */
    public static function render_week_view(
        int $year,
        int $month,
        array $events
    ): string {
        $todayts = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
        $monthts = mktime(0, 0, 0, $month, 1, $year);
        $refts   = (date('n', $todayts) == $month && date('Y', $todayts) == $year)
            ? $todayts
            : $monthts;
        $dow    = (int)date('N', $refts);
        $monday = $refts - ($dow - 1) * DAYSECS;

        $out  = html_writer::start_div('lcc-week-header') . html_writer::div('', '');
        for ($d = 0; $d < 7; $d++) {
            $ts       = $monday + $d * DAYSECS;
            $istoday = date('Y-m-d', $ts) === date('Y-m-d');
            $numcls  = 'lcc-day-num' . ($istoday ? ' bg-primary text-white rounded-circle' : '');
            $out .= html_writer::div(
                html_writer::tag('span', date('D', $ts))
                . html_writer::div((string)(int)date('j', $ts), $numcls, ['style' => 'margin:0 auto']),
                'lcc-week-header-day'
            );
        }
        $out .= html_writer::end_div();
        $out .= html_writer::start_tag('div', ['style' => 'overflow-y:auto;flex:1']);
        $out .= html_writer::start_div('lcc-week-grid');

        foreach (range(6, 22) as $hour) {
            $out .= html_writer::div(sprintf('%02d:00', $hour), 'lcc-hour-label');
            for ($d = 0; $d < 7; $d++) {
                $ts          = $monday + $d * DAYSECS + $hour * 3600;
                $tsend      = $ts + 3600;
                $slotevents = array_filter($events, fn($e) => $e->timestart >= $ts && $e->timestart < $tsend);
                $inner       = '';
                foreach ($slotevents as $ev) {
                    $color  = s($ev->categorycolor);
                    $inner .= html_writer::tag(
                        'a',
                        '<span style="font-size:.65rem;opacity:.85">' . date('H:i', $ev->timestart) . '</span> '
                        . format_string($ev->name),
                        [
                            'href'  => (new \moodle_url(
                                '/local/calendarcategories/view.php',
                                ['event' => $ev->id]
                            ))->out(),
                            'class' => 'lcc-pill d-block mb-1',
                            'style' => "background:{$color}22;color:{$color};border-left:3px solid {$color};white-space:normal",
                        ]
                    );
                }
                $out .= html_writer::div($inner, 'lcc-week-col');
            }
        }
        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('div');
        return $out;
    }

    /**
     * Render the list view of upcoming events grouped by month.
     *
     * @param array          $events      Upcoming events.
     * @param bool           $canadd      Whether the user can create events.
     * @param \moodle_url    $addeventurl URL to add-event page.
     * @param \core_renderer $output      Page output renderer, for pix_icon().
     * @return string HTML output.
     */
    public static function render_list_view(
        array $events,
        bool $canadd,
        \moodle_url $addeventurl,
        \core_renderer $output
    ): string {
        if (empty($events)) {
            $btn = $canadd
                ? html_writer::tag(
                    'a',
                    get_string('addevent', 'local_calendarcategories'),
                    ['href' => $addeventurl->out(), 'class' => 'btn btn-sm btn-primary']
                )
                : '';
            return html_writer::div(
                html_writer::div($output->pix_icon('i/calendar', ''), 'lcc-list-empty-icon')
                . html_writer::tag('p', get_string('noupcoming', 'local_calendarcategories'))
                . $btn,
                'lcc-list-empty'
            );
        }

        $out    = html_writer::start_div('lcc-list');
        $curmon = '';

        foreach ($events as $ev) {
            $mon = get_string(
                'month_' . strtolower(date('M', $ev->timestart)),
                'local_calendarcategories'
            ) . ' ' . date('Y', $ev->timestart);

            if ($mon !== $curmon) {
                $curmon = $mon;
                $out .= html_writer::div(s($mon), 'lcc-list-month');
            }

            $color   = s($ev->categorycolor);
            $dow     = get_string('day_' . strtolower(date('D', $ev->timestart)), 'local_calendarcategories');
            $daynum = date('j', $ev->timestart);
            $time    = date('H:i', $ev->timestart);

            $out .= html_writer::tag(
                'a',
                '<div class="lcc-list-date">'
                . '<div class="day">' . $daynum . '</div>'
                . '<div class="dow">' . $dow . '</div></div>'
                . '<div class="lcc-list-bar" style="background:' . $color . '"></div>'
                . '<div class="lcc-list-info">'
                . '<div class="lcc-list-title">' . format_string($ev->name) . '</div>'
                . '<div class="lcc-list-meta">' . $time
                . (!empty($ev->location) ? ' · ' . s($ev->location) : '')
                . ' · <span style="color:' . $color . ';font-weight:500">'
                . format_string($ev->categoryname) . '</span>'
                . '</div></div>',
                [
                    'href'  => (new \moodle_url(
                        '/local/calendarcategories/view.php',
                        ['event' => $ev->id]
                    ))->out(),
                    'class' => 'lcc-list-event text-decoration-none text-body',
                ]
            );
        }
        $out .= html_writer::end_div();
        return $out;
    }
}
