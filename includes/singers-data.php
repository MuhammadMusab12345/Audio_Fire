<?php
/* ==========================================================================
   SHARED SINGERS DATA (includes/singers-data.php)
   --------------------------------------------------------------------------
   Single shared data source for the "Full Lineup" artists used by BOTH:
   1. singers.php ("The Full Lineup" section)
   2. album.php (the Glass Carousel & Album overlay)

   Reads from the database `artists` table (section = 'lineup') when available.
   Falls back to the complete 29-artist lineup array when MySQL is empty or
   offline, ensuring 100% visual and behavioural fidelity at all times.
   ========================================================================== */

require_once __DIR__ . '/content.php';

/**
 * Complete fallback list of 29 Lineup artists (matches database.sql).
 */
function af_singers_lineup_fallback()
{
    static $fallback = null;
    if ($fallback !== null) {
        return $fallback;
    }

    $raw = [
        [0,  'Arman Veer',    'Strings that remember every sunset',       'uploads/singers/d-0.webp',  '#ff5fa2', 'Acoustic Pop',     '1 January 1980',    'Lahore, Pakistan',   1998, 'Skyline Records', '1.2M', 'male',   '1999,2001,2004,2005,2007,2010'],
        [1,  'Sana Malik',    'Street poetry with a velvet edge',         'uploads/singers/d-1.webp',  '#9ec81f', 'Hip-Hop / R&B',    '8 June 1983',       'Mumbai, India',      2002, 'Urban Echo',     '3.8M', 'female', '2004,2007,2008,2010,2013,2014'],
        [2,  'Dante Khanna',  'Midnight moods, golden hooks',             'uploads/singers/d-2.webp',  '#ff8a1f', 'Folk Rock',        '15 November 1986',  'Austin, USA',        2006, 'Open Road Music','7.4M', 'male',   '2009,2010,2012,2015,2016,2018'],
        [3,  'Jayden Dutta',  'Where machines learn to feel',             'uploads/singers/d-3.webp',  '#4d86ad', 'Alt R&B',          '22 April 1989',     'Toronto, Canada',    2010, 'Nightshift Co.', '12M',  'male',   '2011,2013,2016,2017,2019,2022'],
        [4,  'Harjot Marlow', 'Loud heart, louder chorus',                'uploads/singers/d-4.webp',  '#8d5cf0', 'Punjabi Pop',      '1 September 1992',  'Amritsar, India',    2014, 'Rang Records',   '24M',  'male',   '2016,2019,2020,2022,2025,2026'],
        [5,  'Nora Knight',   'Colour, swagger and bhangra beats',        'uploads/singers/d-5.webp',  '#13b5a3', 'Electro Soul',     '8 February 1995',   'Berlin, Germany',    2018, 'Pulse Theory',   '41M',  'female', '2021,2022,2024,2026,2026,2026'],
        [6,  'Nikhil Solanki','One song, a thousand late nights',         'uploads/singers/d-6.webp',  '#e23a4e', 'Indie Pop',        '15 July 1998',      'Nashville, USA',     2016, 'Paper Moon',     '86M',  'male',   '2017,2019,2022,2023,2025,2026'],
        [8,  'Sofia Lane',    'Soft voice, stadium-sized soul',           'uploads/singers/d-7.webp',  '#e8b21d', 'Latin Pop',        '22 December 2001',  'Lagos, Nigeria',     2020, 'Sol Dorado',     '1.2M', 'female', '2022,2025,2026,2026,2026,2026'],
        [9,  'Mina Stone',    'Dust roads and wooden strings',            'uploads/singers/d-8.webp',  '#3fb6e8', 'Soul',             '1 May 1982',        'London, UK',         2002, 'Quiet Room',     '3.8M', 'female', '2005,2006,2008,2011,2012,2014'],
        [10, 'Aarav Cross',   'Neon dreams, honest words',                'uploads/singers/d-9.webp',  '#c24fd6', 'Rock / Pop',       '8 October 1985',    'Karachi, Pakistan',  2006, 'Stratus Records','7.4M', 'male',   '2007,2009,2012,2013,2015,2018'],
        [11, 'Marcus Voss',   'The sound of a city after rain',           'uploads/singers/d-10.webp', '#2fc27a', 'Acoustic Pop',     '15 March 1988',     'Delhi, India',       2010, 'Skyline Records','12M',  'male',   '2012,2015,2016,2018,2021,2022'],
        [12, 'Leo Rao',       'Warm vocals for cold evenings',            'uploads/singers/d-11.webp', '#7a8cff', 'Hip-Hop / R&B',    '22 August 1991',    'Los Angeles, USA',   2014, 'Urban Echo',     '24M',  'male',   '2017,2018,2020,2023,2024,2026'],
        [13, 'Ishaan Cole',   'Strings that remember every sunset',       'uploads/singers/d-12.webp', '#ff5fa2', 'Folk Rock',        '1 January 1994',    'Lahore, Pakistan',   2012, 'Open Road Music','41M',  'male',   '2013,2015,2018,2019,2021,2024'],
        [14, 'Rafael Noor',   'Street poetry with a velvet edge',         'uploads/singers/d-13.webp', '#9ec81f', 'Alt R&B',          '8 June 1997',       'Mumbai, India',      2016, 'Nightshift Co.', '86M',  'male',   '2018,2021,2022,2024,2026,2026'],
        [15, 'Omar Sharma',   'Midnight moods, golden hooks',             'uploads/singers/d-14.webp', '#ff8a1f', 'Punjabi Pop',      '15 November 2000',  'Austin, USA',        2020, 'Rang Records',   '1.2M', 'male',   '2023,2024,2026,2026,2026,2026'],
        [16, 'Theo Bell',     'Where machines learn to feel',             'uploads/singers/d-15.webp', '#4d86ad', 'Electro Soul',     '22 April 1981',     'Toronto, Canada',    2002, 'Pulse Theory',   '3.8M', 'male',   '2003,2005,2008,2009,2011,2014'],
        [17, 'Vikram Sen',    'Loud heart, louder chorus',                'uploads/singers/d-16.webp', '#8d5cf0', 'Indie Pop',        '1 September 1984',  'Amritsar, India',    2006, 'Paper Moon',     '7.4M', 'male',   '2008,2011,2012,2014,2017,2018'],
        [18, 'Dylan Ahmed',   'Colour, swagger and bhangra beats',        'uploads/singers/d-17.webp', '#13b5a3', 'Latin Pop',        '8 February 1987',   'Berlin, Germany',    2010, 'Sol Dorado',     '12M',  'male',   '2013,2014,2016,2019,2020,2022'],
        [19, 'Samir Okoro',   'One song, a thousand late nights',         'uploads/singers/d-18.webp', '#e23a4e', 'Soul',             '15 July 1990',      'Nashville, USA',     2008, 'Quiet Room',     '24M',  'male',   '2009,2011,2014,2015,2017,2020'],
        [20, 'Jordan Rivera', 'Soft voice, stadium-sized soul',           'uploads/singers/d-19.webp', '#e8b21d', 'Rock / Pop',       '22 December 1993',  'Lagos, Nigeria',     2012, 'Stratus Records','41M',  'male',   '2014,2017,2018,2020,2023,2024'],
        [21, 'Ravi Ford',     'Dust roads and wooden strings',            'uploads/singers/d-20.webp', '#3fb6e8', 'Acoustic Pop',     '1 May 1996',        'London, UK',         2016, 'Skyline Records','86M',  'male',   '2019,2020,2022,2025,2026,2026'],
        [22, 'Lina Bhatt',    'Neon dreams, honest words',                'uploads/singers/d-21.webp', '#c24fd6', 'Hip-Hop / R&B',    '8 October 1999',    'Karachi, Pakistan',  2020, 'Urban Echo',     '1.2M', 'female', '2021,2023,2026,2026,2026,2026'],
        [23, 'Ayla Rai',      'The sound of a city after rain',           'uploads/singers/d-22.webp', '#2fc27a', 'Folk Rock',        '15 March 1980',     'Delhi, India',       2002, 'Open Road Music','3.8M', 'female', '2004,2007,2008,2010,2013,2014'],
        [24, 'Nora Monroe',   'Warm vocals for cold evenings',            'uploads/singers/d-23.webp', '#7a8cff', 'Alt R&B',          '22 August 1983',    'Los Angeles, USA',   2006, 'Nightshift Co.', '7.4M', 'female', '2009,2010,2012,2015,2016,2018'],
        [25, 'Jayden Quinn',  'Strings that remember every sunset',       'uploads/singers/d-24.webp', '#ff5fa2', 'Punjabi Pop',      '1 January 1986',    'Lahore, Pakistan',   2004, 'Rang Records',   '12M',  'male',   '2005,2007,2010,2011,2013,2016'],
        [26, 'Harjot Reed',   'Street poetry with a velvet edge',         'uploads/singers/d-25.webp', '#9ec81f', 'Electro Soul',     '8 June 1989',       'Mumbai, India',      2008, 'Pulse Theory',   '24M',  'male',   '2010,2013,2014,2016,2019,2020'],
        [27, 'Elias Wolfe',   'Midnight moods, golden hooks',             'uploads/singers/d-26.webp', '#ff8a1f', 'Indie Pop',        '15 November 1992',  'Austin, USA',        2012, 'Paper Moon',     '41M',  'male',   '2015,2016,2018,2021,2022,2024'],
        [28, 'Nikhil Hart',   'Where machines learn to feel',             'uploads/singers/d-27.webp', '#4d86ad', 'Latin Pop',        '22 April 1995',     'Toronto, Canada',    2016, 'Sol Dorado',     '86M',  'male',   '2017,2019,2022,2023,2025,2026'],
        [29, 'Sana Storm',    'Loud heart, louder chorus',                'uploads/singers/d-28.webp', '#8d5cf0', 'Soul',             '1 September 1998',  'Amritsar, India',    2020, 'Quiet Room',     '1.2M', 'female', '2022,2025,2026,2026,2026,2026']
    ];

    $fallback = [];
    foreach ($raw as $item) {
        $id          = $item[0];
        $name        = $item[1];
        $tagline     = $item[2];
        $img         = $item[3];
        $accent      = $item[4];
        $genre       = $item[5];
        $birth       = $item[6];
        $place       = $item[7];
        $debut       = $item[8];
        $label       = $item[9];
        $listeners   = $item[10];
        $gender      = $item[11];
        $yearsArr    = array_map('intval', explode(',', $item[12]));
        $isFemale    = ($gender === 'female');
        $city        = explode(',', $place)[0];
        $firstName   = explode(' ', $name)[0];
        $pronoun     = $isFemale ? 'she' : 'he';
        $vocalType   = $isFemale ? 'vocalist' : 'artist';
        $desc        = "{$firstName} is a " . strtolower($genre) . " {$vocalType} from {$city} who debuted in {$debut}. Known for emotional storytelling and unforgettable live performances, {$pronoun} has built a devoted following.";
        $yearInt     = (int)(preg_match('/\b(?:19|20)\d{2}\b/', $birth, $m) ? $m[0] : ($debut - 22));

        $fallback[] = [
            'id'          => $id,
            'i'           => $id,
            'name'        => $name,
            'n'           => $name,
            'image'       => $img,
            'im'          => $img,
            'accent'      => $accent,
            'c'           => $accent,
            'genre'       => $genre,
            'g'           => $genre,
            'tagline'     => $tagline,
            'tg'          => $tagline,
            'description' => $desc,
            'd'           => $desc,
            'birth'       => $birth,
            'b'           => $birth,
            'yb'          => $yearInt,
            'place'       => $place,
            'p'           => $place,
            'debut'       => $debut,
            'db'          => $debut,
            'label'       => $label,
            'lb'          => $label,
            'listeners'   => $listeners,
            'l'           => $listeners,
            'gender'      => $gender,
            'f'           => $isFemale,
            'years'       => $yearsArr,
            'ys'          => $yearsArr,
            'songs_count' => count($yearsArr),
        ];
    }

    return $fallback;
}

/**
 * Map one `artists` table row to the shared singer shape (keys n/im/c/tg/d/l...).
 * Used by af_get_lineup_singers() and af_album_singers() so both pages render
 * identical structures from the same data.
 */
function af_singer_row_out(array $a)
{
    $birth     = (string) ($a['birth'] ?: '');
    $debut     = (int) ($a['debut'] ?: 0);
    $yearInt   = (int) (preg_match('/\b(?:19|20)\d{2}\b/', $birth, $m) ? $m[0] : ($debut ? $debut - 22 : 1990));
    $isFemale  = (($a['gender'] ?? 'male') === 'female');
    $yearsArr  = [];
    if (!empty($a['years'])) {
        foreach (explode(',', (string) $a['years']) as $y) {
            $y = trim($y);
            if ($y !== '' && is_numeric($y)) { $yearsArr[] = (int) $y; }
        }
    }
    if (empty($yearsArr) && $debut > 0) {
        for ($k = 0; $k < 6; $k++) {
            $yearsArr[] = min(2026, $debut + 2 + $k * 3);
        }
    }
    $name      = (string) $a['name'];
    $genre     = (string) ($a['genre'] ?: 'Artist');
    $place     = (string) ($a['place'] ?: '');
    $city      = explode(',', $place)[0] ?: 'Global';
    $firstName = explode(' ', $name)[0];
    $pronoun   = $isFemale ? 'she' : 'he';
    $vocalType = $isFemale ? 'vocalist' : 'artist';
    $desc      = !empty($a['description'])
        ? (string) $a['description']
        : "{$firstName} is a " . strtolower($genre) . " {$vocalType} from {$city} who debuted in {$debut}. Known for emotional storytelling and unforgettable live performances, {$pronoun} has built a devoted following.";

    return [
        'id'          => (int) $a['id'],
        'i'           => (int) $a['id'],
        'name'        => $name,
        'n'           => $name,
        'image'       => (string) ($a['image'] ?: 'uploads/singers/d-0.webp'),
        'im'          => (string) ($a['image'] ?: 'uploads/singers/d-0.webp'),
        'accent'      => (string) ($a['accent'] ?: '#2E86C1'),
        'c'           => (string) ($a['accent'] ?: '#2E86C1'),
        'genre'       => $genre,
        'g'           => $genre,
        'tagline'     => (string) ($a['tagline'] ?: ''),
        'tg'          => (string) ($a['tagline'] ?: ''),
        'description' => $desc,
        'd'           => $desc,
        'birth'       => $birth,
        'b'           => $birth,
        'yb'          => $yearInt,
        'place'       => $place,
        'p'           => $place,
        'debut'       => $debut,
        'db'          => $debut,
        'label'       => (string) ($a['label'] ?: ''),
        'lb'          => (string) ($a['label'] ?: ''),
        'listeners'   => (string) ($a['listeners'] ?: '1.2M'),
        'l'           => (string) ($a['listeners'] ?: '1.2M'),
        'gender'      => $isFemale ? 'female' : 'male',
        'f'           => $isFemale,
        'years'       => $yearsArr,
        'ys'          => $yearsArr,
        'songs_count' => count($yearsArr),
    ];
}

/**
 * Returns all Lineup singers from database or fallback.
 * Both singers.php and album.php consume this function.
 *
 * @return array
 */
function af_get_lineup_singers()
{
    static $singers = null;
    if ($singers !== null) {
        return $singers;
    }

    $dbArtists = function_exists('af_artists') ? af_artists('lineup') : [];
    if (!empty($dbArtists)) {
        $out = [];
        foreach ($dbArtists as $a) {
            $out[] = af_singer_row_out($a);
        }
        $singers = $out;
        return $singers;
    }

    $singers = af_singers_lineup_fallback();
    return $singers;
}

/**
 * THE single source of truth for the Albums page: every artist flagged
 * `in_albums = 1` (the "Show on Albums page" checkbox in the artist form),
 * newest first - exactly the list album.php renders and admin/albums.php mirrors.
 *
 * DB offline -> static 29-singer fallback; healthy but nothing ticked -> empty
 * list (the admin chose to show nobody).
 *
 * @return array
 */
function af_album_singers()
{
    static $singers = null;
    if ($singers !== null) {
        return $singers;
    }

    global $AF_DB;
    if (!$AF_DB) {
        $singers = af_singers_lineup_fallback();
        return $singers;
    }
    try {
        $rows = af_all('SELECT * FROM artists WHERE in_albums = 1 ORDER BY id DESC');
    } catch (PDOException $e) {
        $singers = af_singers_lineup_fallback();
        return $singers;
    }

    $out = [];
    foreach ($rows as $a) {
        $out[] = af_singer_row_out($a);
    }
    $singers = $out;
    return $singers;
}

/**
 * Albums + songs of the given singers, keyed by singer name, newest album
 * first inside each singer. Shared by album.php (window.AF_ALBUMS for the
 * overlay) and admin/albums.php (the mirror list), so both pages always
 * show the exact same data and track counts.
 *
 * Album claims its singer by artist_id first, then by exact name. Songs come
 * from the shared get_album_songs() helper (album_songs join table + the
 * legacy album links), so the user page and the admin can never drift apart.
 *
 * @return array name => [ {id,title,cover,year,genre,description,tracks,songs[]}, ... ]
 */
function af_albums_data(array $singers)
{
    $out   = [];
    $names = [];
    $ids   = [];
    foreach ($singers as $s) {
        $nm = (string) ($s['n'] ?? ($s['name'] ?? ''));
        if ($nm === '' || in_array($nm, $names, true)) {
            continue;
        }
        $names[] = $nm;
        $ids[]   = (int) ($s['id'] ?? 0);
        $out[$nm] = [];
    }
    if (!$names) {
        return $out;
    }

    $mIds = implode(',', array_fill(0, count($ids), '?'));
    $mNms = implode(',', array_fill(0, count($names), '?'));
    try {
        $als = af_all(
            "SELECT id, title, cover, year, genre, description, artist_id, artist FROM albums
             WHERE artist_id IN ($mIds) OR artist IN ($mNms)
             ORDER BY id DESC",
            array_merge($ids, $names)
        );
    } catch (PDOException $e) {
        return $out;
    }

    $idToNm = [];
    foreach ($singers as $s) {
        $idToNm[(int) ($s['id'] ?? 0)] = (string) ($s['n'] ?? ($s['name'] ?? ''));
    }
    foreach ($als as $al) {
        $own = '';
        if (!empty($al['artist_id']) && isset($idToNm[(int) $al['artist_id']])) {
            $own = $idToNm[(int) $al['artist_id']];
        } elseif (in_array((string) $al['artist'], $names, true)) {
            $own = (string) $al['artist'];
        } else {
            continue;
        }
        $key = (int) $al['id'];
        /* one shared query: album_songs join + the legacy album links */
        $list = get_album_songs($key);
        $songs = [];
        foreach ($list as $so) {
            $songs[] = [
                't' => (string) $so['title'],
                'a' => (string) ($so['artist'] ?: $own),
                'i' => (string) ($so['thumb'] ?: ($al['cover'] ?: '')),
                's' => (string) ($so['media'] ?: ''),
                'd' => (int) ($so['duration'] ?: 0),
            ];
        }
        $out[$own][] = [
            'id'          => $key,
            'title'       => (string) $al['title'],
            'cover'       => (string) ($al['cover'] ?: ''),
            'year'        => (int) ($al['year'] ?: 0),
            'genre'       => (string) ($al['genre'] ?: ''),
            'description' => (string) ($al['description'] ?: ''),
            'tracks'      => count($songs),
            'songs'       => $songs,
        ];
    }
    return $out;
}

