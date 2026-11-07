<?php
/* ==========================================================================
   PUBLIC CONTENT (includes/content.php)
   Every row the public site shows, read from the database, with a safe
   fallback to the original hard-coded data when a table is empty or MySQL
   is down. Design and markup stay exactly as they were.

   Usage:  require_once __DIR__ . '/includes/content.php';

     af_songs('music')      -> MP3 rows for Music / Album / home Now Playing
     af_songs('video')      -> video rows for the Songs library
     af_songs('spot')       -> 16 hero spotlight titles (singers page)
     af_artists('lineup')   -> artists of one section, ordered
     af_setting('site_name', 'Audio Fire')
     af_settings()          -> all settings as one map
     af_genres() / af_languages() / af_regions()
   ========================================================================== */

require_once __DIR__ . '/auth.php';          // session + $AF_DB + e()/af_csrf()

/* ------------------------------------------------------------ song arrays */

/**
 * Songs of one section as associative arrays (empty array when unavailable).
 * @param string $section 'music' | 'video' | 'spot'
 */
function af_songs($section = 'music', $limit = 0)
{
    global $AF_DB;
    static $cache = [];
    if (!isset($cache[$section])) {
        $cache[$section] = [];
        if ($AF_DB) {
            try {
                /* music + video are newest-first; the spot hero keeps its
                   curated sort_order (it is not a browse list) */
                $sql = $section === 'spot'
                    ? 'SELECT * FROM songs WHERE section = ? ORDER BY sort_order ASC, id ASC'
                    : 'SELECT * FROM songs WHERE section = ? ORDER BY id DESC';
                if ($limit > 0) {
                    $sql .= ' LIMIT ' . (int) $limit;
                }
                $rows = af_all($sql, [(string) $section]);
                foreach ($rows as $r) {
                    $cache[$section][] = $r;
                }
            } catch (PDOException $e) {
                $cache[$section] = [];
            }
        }
    }
    return $cache[$section];
}

/**
 * Songs of one album - the single source BOTH the Album page overlay and
 * admin/albums.php render, so the user page and the admin mirror can never
 * show different tracks.
 *
 * Membership comes from the album_songs join table (one song may live in any
 * number of albums, ordered by sort_order); the older `songs.album_id` and
 * `songs.album = "<title>"` links are merged in afterwards so songs picked in
 * the Songs / Music admin keep showing up too. Duplicates are dropped.
 *
 * @param int $albumId
 * @return array rows: id, section, title, artist, album, thumb, media, duration
 */
function get_album_songs($albumId)
{
    global $AF_DB;
    $albumId = (int) $albumId;
    static $cache = [];
    if (isset($cache[$albumId])) {
        return $cache[$albumId];
    }

    $songs = [];
    if ($AF_DB && $albumId > 0) {
        $sel = 'SELECT s.id, s.section, s.title, s.artist, s.album, s.album_id, s.thumb, s.media, s.duration';

        /* 1. the album_songs join table (album page order) */
        try {
            foreach (af_all(
                $sel . ' FROM album_songs a JOIN songs s ON s.id = a.song_id
                        WHERE a.album_id = ? ORDER BY a.sort_order ASC, a.id ASC',
                [$albumId]
            ) as $r) {
                $songs[(int) $r['id']] = $r;
            }
        } catch (PDOException $e) {
            /* table not created yet - the legacy links below still answer */
        }

        /* 2. legacy link: the song's own album_id points at this album */
        try {
            foreach (af_all($sel . ' FROM songs s WHERE s.album_id = ? ORDER BY s.id DESC', [$albumId]) as $r) {
                $id = (int) $r['id'];
                if (!isset($songs[$id])) {
                    $songs[$id] = $r;
                }
            }
        } catch (PDOException $e) {
            /* keep whatever was found so far */
        }

        /* 3. oldest link: the song only carries the album's title as text */
        try {
            $al = af_row('SELECT title FROM albums WHERE id = ? LIMIT 1', [$albumId]);
            $ttl = $al ? (string) $al['title'] : '';
            if ($ttl !== '') {
                foreach (af_all(
                    $sel . " FROM songs s WHERE s.album = ? AND (s.album_id IS NULL OR s.album_id = 0) ORDER BY s.id DESC",
                    [$ttl]
                ) as $r) {
                    $id = (int) $r['id'];
                    if (!isset($songs[$id])) {
                        $songs[$id] = $r;
                    }
                }
            }
        } catch (PDOException $e) {
            /* keep whatever was found so far */
        }
    }

    $cache[$albumId] = array_values($songs);
    return $cache[$albumId];
}

/** Music rows shaped exactly like the old AF_SONGS objects. */
function af_music_songs_js()
{
    $out = [];
    foreach (af_songs('music') as $s) {
        $out[] = [
            't' => $s['title'],
            'a' => $s['artist'],
            'l' => $s['language'] ?? 'English',
            'i' => (string) ($s['thumb'] ?? ''),
            's' => (string) ($s['media'] ?? ''),
            'd' => (int) ($s['duration'] ?? 0),
            'c' => (string) ($s['color'] ?? '#5091b2'),
        ];
    }
    return $out;
}

/** Distinct languages of the music set, ["All", ...] first (old AF_LANGS). */
function af_languages_js()
{
    global $AF_DB;
    $langs = [];
    if ($AF_DB) {
        try {
            foreach (af_all("SELECT DISTINCT language FROM songs
                             WHERE section IN ('music','video')
                               AND language IS NOT NULL AND language <> ''
                             ORDER BY language") as $r) {
                $langs[] = $r['language'];
            }
        } catch (PDOException $e) {
            $langs = [];
        }
    }
    if (!$langs) {
        return ['All', 'English', 'Urdu', 'Hindi', 'Korean', 'Spanish', 'Arabic', 'Russian'];
    }
    /* keep the familiar order first, then anything new alphabetically */
    $preferred = ['English', 'Urdu', 'Hindi', 'Korean', 'Spanish', 'Arabic', 'Russian'];
    $known = array_values(array_intersect($preferred, $langs));
    $rest  = array_values(array_diff($langs, $preferred));
    sort($rest);
    return array_merge(['All'], $known, $rest);
}

/* ------------------------------------------------- original fallback data */

/** The five home console cards exactly as they were hard-coded in index.php. */
function af_console_fallback()
{
    return [
        ['name' => 'Rihanna',        'role' => 'Pop & R&B Icon',       'description' => 'Global superstar behind Umbrella and Diamonds, who also built the Fenty empire.', 'place' => 'Barbados', 'image' => 'images/console/rihanna.webp',        'accent' => '#ff5fa2'],
        ['name' => 'Kendrick Lamar', 'role' => 'Rapper',               'description' => 'Poetic storyteller and Pulitzer Prize winner for his album DAMN.',                'place' => 'Compton',  'image' => 'images/console/kendrick-lamar.webp', 'accent' => '#9ec81f'],
        ['name' => 'The Weeknd',     'role' => 'Singer-Songwriter',    'description' => 'Dark, cinematic R&B and synth-pop voice behind the chart-topper Blinding Lights.','place' => 'Toronto',  'image' => 'images/console/the-weeknd.webp',     'accent' => '#ff8a1f'],
        ['name' => 'G-Eazy',         'role' => 'Rapper',               'description' => 'Bay Area rapper with a slick, vintage-cool style, known for Me, Myself & I.',    'place' => 'Oakland',  'image' => 'images/console/g-eazy.webp',         'accent' => '#4d86ad'],
        ['name' => 'Diljit Dosanjh', 'role' => 'Singer & Actor',       'description' => 'Punjabi music and film star who took Desi sound to the Coachella stage.',         'place' => 'Punjab',   'image' => 'images/console/diljit-dosanjh.webp', 'accent' => '#8d5cf0'],
    ];
}

/** The six home "Featured Artists" cards exactly as hard-coded in index.php. */
function af_home_artists_fallback()
{
    $h = [132, 150, 127, 150, 150, 150];
    $r = ['Powerhouse Vocalist', 'Melody Maker', 'Global Superstar', 'Pop Sensation', 'R&B Legend', 'Hip-Hop Icon'];
    $out = [];
    for ($i = 0; $i < 6; $i++) {
        $n = $i + 1;
        $out[] = [
            'name'    => $r[$i],
            'role'    => $r[$i],
            'image'   => 'images/artists/artist-' . $n . '.webp',
            'height'  => $h[$i],
            'save_id' => 'home-artist-' . $n,
            'accent'  => ['#ff5fa2', '#9ec81f', '#ff8a1f', '#4d86ad', '#8d5cf0', '#13b5a3'][$i],
        ];
    }
    return $out;
}

/* ----------------------------------------------------------- artist lists */

/**
 * Artists of one section, ordered.
 * @param string $section console|home|singer|feature|lineup
 */
function af_artists($section = 'lineup')
{
    global $AF_DB;
    static $cache = [];
    if (!isset($cache[$section])) {
        $cache[$section] = [];
        if ($AF_DB) {
            try {
                $cache[$section] = af_all(
                    'SELECT * FROM artists WHERE section = ? ORDER BY id DESC',
                    [(string) $section]
                );
            } catch (PDOException $e) {
                $cache[$section] = [];
            }
        }
    }
    return $cache[$section];
}

/* ---------------------------------------------------------------- settings */

/** All settings as a key => value map (empty array when unavailable). */
function af_settings()
{
    global $AF_DB;
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = [];
    if ($AF_DB) {
        try {
            foreach (af_all('SELECT setting_key, setting_value FROM settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache;
}

/** One setting with a default. */
function af_setting($key, $default = '')
{
    $all = af_settings();
    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}

/* ---------------------------------------------------------------- filters */

function af_genres()
{
    return af_category_names('genre');
}
function af_languages()
{
    return af_category_names('language');
}
function af_regions()
{
    return af_category_names('region');
}

/** Names of one category kind (falls back to values found in the songs). */
function af_category_names($kind)
{
    global $AF_DB;
    $out = [];
    if ($AF_DB) {
        try {
            foreach (af_all('SELECT name FROM categories WHERE kind = ? ORDER BY sort_order ASC, id ASC', [(string) $kind]) as $r) {
                $out[] = $r['name'];
            }
        } catch (PDOException $e) {
            $out = [];
        }
    }
    if ($out) {
        return $out;
    }
    /* derive from whatever the songs actually carry */
    if ($AF_DB) {
        try {
            $col = $kind === 'genre' ? 'genre' : ($kind === 'region' ? 'region' : 'language');
            foreach (af_all("SELECT DISTINCT {$col} v FROM songs WHERE {$col} IS NOT NULL AND {$col} <> '' ORDER BY {$col}") as $r) {
                $out[] = $r['v'];
            }
        } catch (PDOException $e) {
            $out = [];
        }
    }
    return $out;
}
