<?php
/* ==========================================================================
   SONGS PAGE (songs.php)
   Full music & video library - filters, search, sorting, video modal and
   reviews. Ported from the standalone "Songs_Page" build onto this site's
   shared head / navbar / footer.

   Layout of this file:
     1. data block      $songs + $heroSongs (unchanged from the original)
     2. shared <head>   includes/head.php      ($pageStyles / $pageFontHref)
     3. shared navbar   includes/navbar.php    (Home - Singers - Songs - Music
                                                - Album - Reviews - Contact)
     4. music hero      includes/music-hero.php
     5. library markup  unchanged
     6. page script     kept inline because it echoes $demoReviews from PHP
     7. shared footer   includes/footer.php + includes/scripts.php
   ========================================================================== */

$currentPage     = 'songs';
$pageTitle       = 'Songs | Audio Fire';
$metaDescription = 'Browse the Audio Fire library - every song with artist, album, year, genre and language filters plus the music video player.';
$pageFontHref    = 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@800&display=swap';
$pageStyles      = array('assets/css/music-hero.css', 'assets/css/songs-page.css');

/*
|--------------------------------------------------------------------------
| SOUND GROUP - MUSIC & VIDEO LIBRARY
|--------------------------------------------------------------------------
| Features:
| Album | Artist | Year | Genre | Language
| Search | Sorting | Regional + English | Video Player
| Demo Reviews + User Reviews (localStorage)
|--------------------------------------------------------------------------
*/

$songs = [

    /* ===== ENGLISH / INTERNATIONAL ===== */
    ["title" => "Blinding Lights", "artist" => "The Weeknd", "album" => "After Hours", "year" => 2020, "genre" => "Synth-pop", "language" => "English", "region" => "International", "thumb" => "uploads/rihanna.webp", "video" => "uploads/sample1.mp4"],
    ["title" => "HUMBLE.", "artist" => "Kendrick Lamar", "album" => "DAMN.", "year" => 2017, "genre" => "Hip-Hop", "language" => "English", "region" => "International", "thumb" => "uploads/kendrick-lamar.webp", "video" => "uploads/sample2.mp4"],
    ["title" => "Starboy", "artist" => "The Weeknd", "album" => "Starboy", "year" => 2016, "genre" => "R&B", "language" => "English", "region" => "International", "thumb" => "uploads/the-weeknd.webp", "video" => "uploads/sample3.mp4"],
    ["title" => "Good Life", "artist" => "G-Eazy", "album" => "The Beautiful & Damned", "year" => 2017, "genre" => "Hip-Hop", "language" => "English", "region" => "International", "thumb" => "uploads/g-eazy.webp", "video" => "uploads/sample4.mp4"],
    ["title" => "What Was I Made For?", "artist" => "Billie Eilish", "album" => "Barbie: The Album", "year" => 2023, "genre" => "Alternative", "language" => "English", "region" => "International", "thumb" => "uploads/artist-1.webp", "video" => "uploads/sample6.mp4"],
    ["title" => "Uptown Funk", "artist" => "Bruno Mars", "album" => "Uptown Special", "year" => 2015, "genre" => "Funk", "language" => "English", "region" => "International", "thumb" => "uploads/artist-34.webp", "video" => "uploads/sample33.mp4"],
    ["title" => "Into You", "artist" => "Ariana Grande", "album" => "Dangerous Woman", "year" => 2016, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-35.webp", "video" => "uploads/sample34.mp4"],
    ["title" => "Lose Yourself", "artist" => "Eminem", "album" => "8 Mile", "year" => 2002, "genre" => "Hip-Hop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-36.webp", "video" => "uploads/sample35.mp4"],
    ["title" => "Sorry", "artist" => "Justin Bieber", "album" => "Purpose", "year" => 2015, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-37.webp", "video" => "uploads/sample36.mp4"],
    ["title" => "Bad Romance", "artist" => "Lady Gaga", "album" => "The Fame Monster", "year" => 2009, "genre" => "Dance-pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-38.webp", "video" => "uploads/sample37.mp4"],
    ["title" => "Halo", "artist" => "Beyoncé", "album" => "I Am... Sasha Fierce", "year" => 2008, "genre" => "R&B", "language" => "English", "region" => "International", "thumb" => "uploads/artist-39.webp", "video" => "uploads/sample38.mp4"],
    ["title" => "Firework", "artist" => "Katy Perry", "album" => "Teenage Dream", "year" => 2010, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-40.webp", "video" => "uploads/sample39.mp4"],
    ["title" => "Billie Jean", "artist" => "Michael Jackson", "album" => "Thriller", "year" => 1982, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-41.webp", "video" => "uploads/sample40.mp4"],
    ["title" => "Diamonds", "artist" => "Rihanna", "album" => "Unapologetic", "year" => 2012, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-42.webp", "video" => "uploads/sample41.mp4"],
    ["title" => "Hello", "artist" => "Adele", "album" => "25", "year" => 2015, "genre" => "Soul", "language" => "English", "region" => "International", "thumb" => "uploads/artist-43.webp", "video" => "uploads/sample42.mp4"],
    ["title" => "Drivers License", "artist" => "Olivia Rodrigo", "album" => "SOUR", "year" => 2021, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-105.webp", "video" => "uploads/sample74.mp4"],
    ["title" => "Shape of You", "artist" => "Ed Sheeran", "album" => "Divide", "year" => 2017, "genre" => "Pop", "language" => "English", "region" => "International", "thumb" => "uploads/artist-106.webp", "video" => "uploads/sample83.mp4"],

    /* ===== LATIN / SPANISH ===== */
    ["title" => "Waka Waka", "artist" => "Shakira", "album" => "Listen Up! The Official 2010 FIFA World Cup Album", "year" => 2010, "genre" => "Latin Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-7.webp", "video" => "uploads/sample7.mp4"],
    ["title" => "Bailando", "artist" => "Enrique Iglesias", "album" => "Sex and Love", "year" => 2014, "genre" => "Latin Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-8.webp", "video" => "uploads/sample8.mp4"],
    ["title" => "Tití Me Preguntó", "artist" => "Bad Bunny", "album" => "Un Verano Sin Ti", "year" => 2022, "genre" => "Reggaeton", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-9.webp", "video" => "uploads/sample9.mp4"],
    ["title" => "Despacito", "artist" => "Luis Fonsi", "album" => "Vida", "year" => 2019, "genre" => "Reggaeton", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-10.webp", "video" => "uploads/sample10.mp4"],
    ["title" => "Malamente", "artist" => "Rosalía", "album" => "El Mal Querer", "year" => 2018, "genre" => "Flamenco Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-11.webp", "video" => "uploads/sample11.mp4"],
    ["title" => "Gasolina", "artist" => "Daddy Yankee", "album" => "Barrio Fino", "year" => 2004, "genre" => "Reggaeton", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-12.webp", "video" => "uploads/sample12.mp4"],
    ["title" => "Felices los 4", "artist" => "Maluma", "album" => "F.A.M.E.", "year" => 2018, "genre" => "Latin Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-13.webp", "video" => "uploads/sample13.mp4"],
    ["title" => "Mi Gente", "artist" => "J Balvin", "album" => "Vibras", "year" => 2018, "genre" => "Reggaeton", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-14.webp", "video" => "uploads/sample14.mp4"],
    ["title" => "Livin' la Vida Loca", "artist" => "Ricky Martin", "album" => "Ricky Martin", "year" => 1999, "genre" => "Latin Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-15.webp", "video" => "uploads/sample15.mp4"],
    ["title" => "TQG", "artist" => "Karol G", "album" => "Mañana Será Bonito", "year" => 2023, "genre" => "Reggaeton", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-16.webp", "video" => "uploads/sample16.mp4"],
    ["title" => "Corazón Partío", "artist" => "Alejandro Sanz", "album" => "Más", "year" => 1997, "genre" => "Latin Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-17.webp", "video" => "uploads/sample17.mp4"],
    ["title" => "Shower", "artist" => "Becky G", "album" => "Play It Again", "year" => 2013, "genre" => "Pop", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-18.webp", "video" => "uploads/sample18.mp4"],
    ["title" => "Se Preparó", "artist" => "Ozuna", "album" => "Odisea", "year" => 2017, "genre" => "Reggaeton", "language" => "Spanish", "region" => "Latin", "thumb" => "uploads/artist-19.webp", "video" => "uploads/sample19.mp4"],

    /* ===== K-POP ===== */
    ["title" => "Like Crazy", "artist" => "Jimin", "album" => "FACE", "year" => 2023, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-20.webp", "video" => "uploads/sample20.mp4"],
    ["title" => "Seven", "artist" => "Jungkook", "album" => "Golden", "year" => 2023, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-21.webp", "video" => "uploads/sample21.mp4"],
    ["title" => "Love Me Again", "artist" => "V", "album" => "Layover", "year" => 2023, "genre" => "R&B", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-22.webp", "video" => "uploads/sample22.mp4"],
    ["title" => "Celebrity", "artist" => "IU", "album" => "LILAC", "year" => 2021, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-23.webp", "video" => "uploads/sample23.mp4"],
    ["title" => "Solo", "artist" => "Jennie", "album" => "Solo", "year" => 2018, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-24.webp", "video" => "uploads/sample24.mp4"],
    ["title" => "Money", "artist" => "Lisa", "album" => "LALISA", "year" => 2021, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-25.webp", "video" => "uploads/sample25.mp4"],
    ["title" => "On The Ground", "artist" => "Rosé", "album" => "R", "year" => 2021, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-26.webp", "video" => "uploads/sample26.mp4"],
    ["title" => "Flower", "artist" => "Jisoo", "album" => "ME", "year" => 2023, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-27.webp", "video" => "uploads/sample27.mp4"],
    ["title" => "Crooked", "artist" => "G-Dragon", "album" => "Coup d'Etat", "year" => 2013, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-28.webp", "video" => "uploads/sample28.mp4"],
    ["title" => "INVU", "artist" => "Taeyeon", "album" => "INVU", "year" => 2022, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-29.webp", "video" => "uploads/sample29.mp4"],
    ["title" => "Gangnam Style", "artist" => "PSY", "album" => "PSY 6", "year" => 2012, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-30.webp", "video" => "uploads/sample30.mp4"],
    ["title" => "Candy", "artist" => "Baekhyun", "album" => "Delight", "year" => 2020, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-31.webp", "video" => "uploads/sample31.mp4"],
    ["title" => "Arson", "artist" => "J-Hope", "album" => "Jack In The Box", "year" => 2022, "genre" => "K-Pop", "language" => "Korean", "region" => "K-Pop", "thumb" => "uploads/artist-32.webp", "video" => "uploads/sample32.mp4"],

    /* ===== INDIAN ===== */
    ["title" => "Tum Hi Ho", "artist" => "Arijit Singh", "album" => "Aashiqui 2", "year" => 2013, "genre" => "Bollywood", "language" => "Hindi", "region" => "Indian", "thumb" => "uploads/artist-44.webp", "video" => "uploads/sample43.mp4"],
    ["title" => "Kal Ho Naa Ho", "artist" => "Sonu Nigam", "album" => "Kal Ho Naa Ho", "year" => 2003, "genre" => "Bollywood", "language" => "Hindi", "region" => "Indian", "thumb" => "uploads/artist-46.webp", "video" => "uploads/sample44.mp4"],
    ["title" => "DJ Waley Babu", "artist" => "Badshah", "album" => "One", "year" => 2015, "genre" => "Indian Pop", "language" => "Hindi", "region" => "Indian", "thumb" => "uploads/artist-56.webp", "video" => "uploads/sample45.mp4"],

    /* ===== RUSSIAN ===== */
    ["title" => "Believe", "artist" => "Dima Bilan", "album" => "Believe", "year" => 2008, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-57.webp", "video" => "uploads/sample46.mp4"],
    ["title" => "Million Roses", "artist" => "Alla Pugacheva", "album" => "Million Roses", "year" => 1983, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-58.webp", "video" => "uploads/sample47.mp4"],
    ["title" => "Snow", "artist" => "Philipp Kirkorov", "album" => "Snow", "year" => 1995, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-59.webp", "video" => "uploads/sample48.mp4"],
    ["title" => "A Million Voices", "artist" => "Polina Gagarina", "album" => "A Million Voices", "year" => 2015, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-60.webp", "video" => "uploads/sample49.mp4"],
    ["title" => "You Are the Only One", "artist" => "Sergey Lazarev", "album" => "The Best", "year" => 2016, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-61.webp", "video" => "uploads/sample50.mp4"],
    ["title" => "Iskala", "artist" => "Zemfira", "album" => "Forgive Me My Love", "year" => 2000, "genre" => "Rock", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-62.webp", "video" => "uploads/sample51.mp4"],
    ["title" => "Captain", "artist" => "Miyagi", "album" => "Captain", "year" => 2017, "genre" => "Hip-Hop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-63.webp", "video" => "uploads/sample52.mp4"],
    ["title" => "Cadillac", "artist" => "Morgenshtern", "album" => "Cadillac", "year" => 2020, "genre" => "Hip-Hop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-64.webp", "video" => "uploads/sample53.mp4"],
    ["title" => "Lyubimka", "artist" => "NILETTO", "album" => "Lyubimka", "year" => 2019, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-65.webp", "video" => "uploads/sample54.mp4"],
    ["title" => "Life", "artist" => "Zivert", "album" => "Vinyl #2", "year" => 2021, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-66.webp", "video" => "uploads/sample55.mp4"],
    ["title" => "Opera #2", "artist" => "Vitas", "album" => "Philosophy of Miracle", "year" => 2001, "genre" => "Pop", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-67.webp", "video" => "uploads/sample56.mp4"],
    ["title" => "Skibidi", "artist" => "Little Big", "album" => "Antipositive", "year" => 2018, "genre" => "Electronic", "language" => "Russian", "region" => "Russian", "thumb" => "uploads/artist-68.webp", "video" => "uploads/sample57.mp4"],

    /* ===== ARABIC ===== */
    ["title" => "Tamally Maak", "artist" => "Amr Diab", "album" => "Aktar Wahed", "year" => 2000, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-69.webp", "video" => "uploads/sample58.mp4"],
    ["title" => "Ah W Noss", "artist" => "Nancy Ajram", "album" => "Ah W Noss", "year" => 2004, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-70.webp", "video" => "uploads/sample59.mp4"],
    ["title" => "Nassam Alayna El Hawa", "artist" => "Fairuz", "album" => "Fairuz", "year" => 1967, "genre" => "Arabic Classic", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-71.webp", "video" => "uploads/sample60.mp4"],
    ["title" => "Enta Omri", "artist" => "Umm Kulthum", "album" => "Enta Omri", "year" => 1964, "genre" => "Arabic Classic", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-72.webp", "video" => "uploads/sample61.mp4"],
    ["title" => "Aayshalak", "artist" => "Elissa", "album" => "Ayami Beek", "year" => 2007, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-73.webp", "video" => "uploads/sample62.mp4"],
    ["title" => "Eih Elly Ghayarak", "artist" => "Tamer Hosny", "album" => "Eish Besho'ak", "year" => 2008, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-74.webp", "video" => "uploads/sample63.mp4"],
    ["title" => "Zidini Ishqan", "artist" => "Kadim Al Sahir", "album" => "Zidini Ishqan", "year" => 1999, "genre" => "Arabic Classic", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-75.webp", "video" => "uploads/sample64.mp4"],
    ["title" => "Ma Balash", "artist" => "Mohamed Hamaki", "album" => "Kol Youm Men Da", "year" => 2019, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-76.webp", "video" => "uploads/sample65.mp4"],
    ["title" => "Nassini El Donya", "artist" => "Ragheb Alama", "album" => "Nassini El Donya", "year" => 1990, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-77.webp", "video" => "uploads/sample66.mp4"],
    ["title" => "Kalam Eineh", "artist" => "Sherine", "album" => "Lazem Ayesh", "year" => 2009, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-78.webp", "video" => "uploads/sample67.mp4"],
    ["title" => "Lm3allem", "artist" => "Saad Lamjarred", "album" => "Lm3allem", "year" => 2015, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-79.webp", "video" => "uploads/sample68.mp4"],
    ["title" => "Bent Akaber", "artist" => "Assala Nasri", "album" => "Bent Akaber", "year" => 2019, "genre" => "Arabic Pop", "language" => "Arabic", "region" => "Arabic", "thumb" => "uploads/artist-80.webp", "video" => "uploads/sample69.mp4"],

    /* ===== URDU / PAKISTANI ===== */
    ["title" => "Tera Hone Laga Hoon", "artist" => "Atif Aslam", "album" => "Ajab Prem Ki Ghazab Kahani", "year" => 2009, "genre" => "Pop", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-81.webp", "video" => "uploads/sample70.mp4"],
    ["title" => "Channo", "artist" => "Ali Zafar", "album" => "Huqa Pani", "year" => 2003, "genre" => "Pop", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-82.webp", "video" => "uploads/sample71.mp4"],
    ["title" => "Afreen Afreen", "artist" => "Rahat Fateh Ali Khan", "album" => "Coke Studio", "year" => 2016, "genre" => "Sufi", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-83.webp", "video" => "uploads/sample72.mp4"],
    ["title" => "Mere Rashke Qamar", "artist" => "Nusrat Fateh Ali Khan", "album" => "Mustt Mustt", "year" => 1990, "genre" => "Qawwali", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-84.webp", "video" => "uploads/sample73.mp4"],
    ["title" => "Pasoori", "artist" => "Ali Sethi", "album" => "Coke Studio 14", "year" => 2022, "genre" => "Fusion", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-86.webp", "video" => "uploads/sample75.mp4"],
    ["title" => "Jo Tu Na Mila", "artist" => "Asim Azhar", "album" => "Jo Tu Na Mila", "year" => 2018, "genre" => "Pop", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-87.webp", "video" => "uploads/sample76.mp4"],
    ["title" => "Afreen Afreen", "artist" => "Momina Mustehsan", "album" => "Coke Studio", "year" => 2016, "genre" => "Sufi", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-88.webp", "video" => "uploads/sample77.mp4"],
    ["title" => "Roiyaan", "artist" => "Farhan Saeed", "album" => "Roiyaan", "year" => 2017, "genre" => "Pop", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-89.webp", "video" => "uploads/sample78.mp4"],
    ["title" => "Boohey Bariyan", "artist" => "Hadiqa Kiani", "album" => "Raqeeb Se", "year" => 1995, "genre" => "Pop", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-90.webp", "video" => "uploads/sample79.mp4"],
    ["title" => "Cinderella", "artist" => "Sajjad Ali", "album" => "Cinderella", "year" => 2005, "genre" => "Pop", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-91.webp", "video" => "uploads/sample80.mp4"],
    ["title" => "Duur", "artist" => "Strings", "album" => "Dhaani", "year" => 2003, "genre" => "Rock", "language" => "Urdu", "region" => "Pakistan", "thumb" => "uploads/artist-92.webp", "video" => "uploads/sample81.mp4"]
];

/* ==========================================================================
   LIVE SONGS - rows saved from the admin Videos form (section = "video").
   They are prepended so the newest admin additions sit at the top of the
   grid (af_songs already returns ORDER BY id DESC). NULL columns fall back
   to safe defaults so an incomplete row still renders a full card. The
   static library below only fills titles the database does not already
   carry, so nothing is ever listed twice.
   ========================================================================== */
require_once __DIR__ . '/includes/content.php';

$staticSongs = $songs;
$liveSongs = [];
foreach (af_songs('video') as $v) {
    $liveSongs[] = [
        "id"       => (int) $v["id"],
        "title"    => (string) $v["title"],
        "artist"   => (string) ($v["artist"] ?: "Various Artists"),
        "album"    => (string) ($v["album"] ?: ""),
        "year"     => (int) ($v["year"] ?: 0),
        "genre"    => (string) ($v["genre"] ?: ""),
        "language" => (string) ($v["language"] ?: ""),
        "region"   => (string) ($v["region"] ?: ""),
        "thumb"    => (string) ($v["thumb"] ?: ""),
        "video"    => (string) ($v["media"] ?: ""),
    ];
}
$songs  = $liveSongs;
$seen   = [];
foreach ($liveSongs as $l) {
    $seen[$l["title"] . "|" . $l["artist"]] = true;
}
foreach ($staticSongs as $s) {
    if (isset($seen[$s["title"] . "|" . $s["artist"]])) continue;
    $songs[] = $s;
}


/* =========================
   FILTER DATA
   ========================= */

$artists = [];
$albums = [];
$years = [];
$genres = [];
$languages = [];

foreach ($songs as $song) {
    $artists[] = $song["artist"];
    if ($song["album"] !== "")    $albums[]    = $song["album"];
    if ($song["year"] > 0)        $years[]     = $song["year"];
    if ($song["genre"] !== "")    $genres[]    = $song["genre"];
    if ($song["language"] !== "") $languages[] = $song["language"];
}

$artists = array_values(array_unique($artists));
$albums = array_values(array_unique($albums));
$years = array_values(array_unique($years));
$genres = array_values(array_unique($genres));
$languages = array_values(array_unique($languages));

sort($artists);
sort($albums);
rsort($years);
sort($genres);
sort($languages);

$totalSongs = count($songs);
$totalArtists = count($artists);
$totalAlbums = count($albums);
$totalGenres = count($genres);


/* =========================
   DEMO REVIEWS
   ========================= */

$demoNames = ["Ayesha","Hamza","Sara","Bilal","Zainab","Usman","Fatima","Ali","Hira","Daniyal","Maryam","Ahmed"];
$demoComments = [
    "Zabardast song, baar baar sunne ka mann karta hai!",
    "Video quality bohat acchi hai.",
    "Lyrics aur music dono kamaal.",
    "Mera favourite track, daily sunta hun.",
    "Overall decent, thora aur behtar ho sakta tha.",
    "Beat bohat mast hai, party ke liye perfect.",
    "Dil ko choo gaya, bohat sukoon milta hai.",
    "Classic hai, jitni baar suno naya lagta hai.",
    "Voice amazing hai!",
    "Sound Group par dekh kar maza aa gaya."
];
$demoRatings = [5,4,5,4,3,5,4];

$demoReviews = [];
foreach ($songs as $s) {
    $id   = md5($s["title"] . "|" . $s["artist"]);
    $seed = hexdec(substr($id, 0, 8));
    $list = [];
    for ($i = 0; $i < 3; $i++) {
        $list[] = [
            "name"    => $demoNames[($seed + $i * 3) % count($demoNames)],
            "rating"  => $demoRatings[($seed + $i * 5) % count($demoRatings)],
            "comment" => $demoComments[($seed + $i * 7) % count($demoComments)],
            "date"    => "2025-0" . (1 + ($seed + $i) % 9) . "-1" . ($i + 1)
        ];
    }
    $demoReviews[$id] = $list;
}

/* =========================
   HERO SONGS (top of page)
   Each pick = [title, artist, landscape image, accent colour, length in seconds, one-line description]
   The same landscape image replaces that song's thumbnail in the library grid below.
   To change a hero song, edit a row. To add a 10th, copy hero-10.jpg + hero-10-sm.jpg
   into assets/hero/ and add one more row.
   ========================= */

$heroPicks = [
    ["Tum Hi Ho",          "Arijit Singh",          "hero-01", "#f0a35e", 137, "Two people, one quiet corridor, and a promise that sounds softer than it is. Pure romance."],
    ["Tera Hone Laga Hoon","Atif Aslam",            "hero-02", "#ff5470", 224, "A voice that lands like a spotlight. Warm, soaring and impossible to skip."],
    ["Kal Ho Naa Ho",      "Sonu Nigam",            "hero-03", "#ff6b6b", 168, "One guitar, one spotlight, one voice. Poetry sung exactly the way it was meant to be heard."],
    ["Pasoori",            "Ali Sethi",             "hero-04", "#d9e84a", 308, "Sunlight through green leaves and a melody that wanders. Folk meets fusion by the water."],
    ["Mere Rashke Qamar",  "Nusrat Fateh Ali Khan", "hero-05", "#f0a050", 392, "A timeless classic printed in halftone memories. Nostalgic and instantly familiar."],
    ["Diamonds",           "Rihanna",               "hero-06", "#f2c76e", 239, "Golden hour by the pool, palms in the haze. Glossy, confident and made for summer."],
    ["Afreen Afreen",      "Rahat Fateh Ali Khan",  "hero-07", "#ff7a3d", 420, "Ink-blue waves and a single autumn leaf. A slow Sufi melody that carries you home."],
    ["Blinding Lights",    "The Weeknd",            "hero-08", "#ff4d4d", 216, "Red neon on an empty stage. A midnight drive with the volume all the way up."],
    ["Hello",              "Adele",                 "hero-09", "#6fd3f2", 170, "Quiet hands, a heavy heart and a voice that fills the room. Made for late nights."],
];

$heroSongs = [];
foreach ($heroPicks as $pick) {
    foreach ($songs as $k => $s) {
        if ($s["title"] !== $pick[0] || $s["artist"] !== $pick[1]) continue;

        $id = md5($s["title"] . "|" . $s["artist"]);

        // same landscape image becomes the thumbnail in the library grid
        $songs[$k]["thumb"] = "assets/hero/" . $pick[2] . ".jpg";

        $ratings = array_column($demoReviews[$id], "rating");
        $avg     = count($ratings) ? array_sum($ratings) / count($ratings) : 4.5;
        $seed    = hexdec(substr($id, 0, 8));

        $heroSongs[] = [
            "id"       => $id,
            "title"    => $s["title"],
            "artist"   => $s["artist"],
            "album"    => $s["album"],
            "year"     => $s["year"],
            "genre"    => $s["genre"],
            "language" => $s["language"],
            "video"    => $s["video"],
            "img"      => "assets/hero/" . $pick[2] . ".jpg",
            "thumb"    => "assets/hero/" . $pick[2] . "-sm.jpg",
            "accent"   => $pick[3],
            "dur"      => $pick[4],
            "desc"     => $pick[5],
            "rating"   => round(max(4.0, min(5.0, $avg + 0.3)), 1),
            "plays"    => (3 + $seed % 96) . "." . ($seed % 10) . "M"
        ];
        break;
    }
}

require __DIR__ . '/includes/head.php';
?>

<?php require __DIR__ . '/includes/navbar.php'; ?>


<!-- Fixed page texture + soft lighting. Colours follow the hero song (see assets/js/music-hero.js) -->
<div id="bg-layer" aria-hidden="true"></div>
<div class="page-lighting" aria-hidden="true">
    <div class="top-glow"></div>
    <div class="light-beam"></div>
</div>


<?php require __DIR__ . '/includes/music-hero.php'; ?>

<main class="library" id="library">

    <!-- STATISTICS -->
    <section class="stats">
        <div class="stat">
            <span class="stat-number"><?php echo $totalSongs; ?></span>
            <span class="stat-label">Songs & Videos</span>
        </div>
        <div class="stat">
            <span class="stat-number"><?php echo $totalArtists; ?></span>
            <span class="stat-label">Artists</span>
        </div>
        <div class="stat">
            <span class="stat-number"><?php echo $totalAlbums; ?></span>
            <span class="stat-label">Albums</span>
        </div>
        <div class="stat">
            <span class="stat-number"><?php echo $totalGenres; ?></span>
            <span class="stat-label">Genres</span>
        </div>
    </section>


    <!-- WORKSPACE -->
    <section class="workspace">

        <!-- SIDEBAR -->
        <aside class="filters">

            <div class="filters-title">
                <h2>Browse Library</h2>
                <button type="button" class="reset-btn" id="resetFilters">Reset</button>
            </div>

            <div class="filter-group">
                <label for="artistFilter">Artist</label>
                <select id="artistFilter">
                    <option value="all">All Artists</option>
                    <?php foreach ($artists as $artist): ?>
                        <option value="<?php echo htmlspecialchars($artist); ?>"><?php echo htmlspecialchars($artist); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="albumFilter">Album</label>
                <select id="albumFilter">
                    <option value="all">All Albums</option>
                    <?php foreach ($albums as $album): ?>
                        <option value="<?php echo htmlspecialchars($album); ?>"><?php echo htmlspecialchars($album); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="yearFilter">Release Year</label>
                <select id="yearFilter">
                    <option value="all">All Years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="genreFilter">Genre</label>
                <select id="genreFilter">
                    <option value="all">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="languageFilter">Language</label>
                <select id="languageFilter">
                    <option value="all">All Languages</option>
                    <?php foreach ($languages as $language): ?>
                        <option value="<?php echo htmlspecialchars($language); ?>"><?php echo htmlspecialchars($language); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </aside>


        <!-- CONTENT -->
        <div class="content">

            <!-- Search + Sort -->
            <div class="content-top">

                <div class="search-box">
                    <input type="search" id="searchInput" placeholder="Search song, artist, album or genre...">
                </div>

                <div class="sort-box">
                    <select id="sortSelect">
                        <option value="default">Featured</option>
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="az">A — Z</option>
                        <option value="za">Z — A</option>
                    </select>
                </div>

            </div>


            <!-- Quick Language Filters -->
            <div class="quick-languages">
                <button class="quick-btn active" data-language="all">All</button>
                <button class="quick-btn" data-language="English">English</button>
                <button class="quick-btn" data-language="Urdu">Urdu</button>
                <button class="quick-btn" data-language="Hindi">Hindi</button>
                <button class="quick-btn" data-language="Korean">Korean</button>
                <button class="quick-btn" data-language="Spanish">Spanish</button>
                <button class="quick-btn" data-language="Arabic">Arabic</button>
                <button class="quick-btn" data-language="Russian">Russian</button>
            </div>


            <!-- Result Count -->
            <div class="result-bar">
                <div class="result-count" id="resultCount">Showing <?php echo $totalSongs; ?> items</div>
                <div class="view-label">Music Collection</div>
            </div>


            <!-- SONG GRID -->
            <div class="songs-grid" id="songsGrid">

                <?php foreach ($songs as $index => $song): ?>
                    <?php $sid = md5($song["title"] . "|" . $song["artist"]); ?>

                    <article
                        class="song-card"
                        data-id="<?php echo $sid; ?>"
                        data-save="song"
                        data-save-id="<?php echo e(af_slug($song["title"] . ' ' . $song["artist"])); ?>"
                        data-save-title="<?php echo htmlspecialchars($song["title"]); ?>"
                        data-save-artist="<?php echo htmlspecialchars($song["artist"]); ?>"
                        data-save-img="<?php echo htmlspecialchars($song["thumb"]); ?>"
                        data-save-href="songs.php"
                        data-title="<?php echo htmlspecialchars(strtolower($song["title"])); ?>"
                        data-artist="<?php echo htmlspecialchars(strtolower($song["artist"])); ?>"
                        data-album="<?php echo htmlspecialchars(strtolower($song["album"])); ?>"
                        data-year="<?php echo $song["year"]; ?>"
                        data-genre="<?php echo htmlspecialchars(strtolower($song["genre"])); ?>"
                        data-language="<?php echo htmlspecialchars(strtolower($song["language"])); ?>"
                        data-region="<?php echo htmlspecialchars(strtolower($song["region"])); ?>"
                        data-video="<?php echo htmlspecialchars($song["video"]); ?>"
                        data-song-title="<?php echo htmlspecialchars($song["title"]); ?>"
                        data-song-artist="<?php echo htmlspecialchars($song["artist"]); ?>"
                    >

                        <div class="thumb">
                            <?php if ($song["thumb"] !== ""): ?>
                                <img
                                    src="<?php echo htmlspecialchars($song["thumb"]); ?>"
                                    alt="<?php echo htmlspecialchars($song["title"]); ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <!-- placeholder when the admin row has no cover yet -->
                                <span class="thumb-ph" aria-hidden="true">&#9835;</span>
                            <?php endif; ?>
                            <?php if ($index < 3): ?>
                                <!-- doc requirement: a flashing "New" icon on new additions -->
                                <span class="af-new">NEW</span>
                            <?php endif; ?>
                            <?php if ($song["year"] > 0): ?>
                                <div class="year-badge"><?php echo $song["year"]; ?></div>
                            <?php endif; ?>
                            <?php if ($song["video"] !== ""): ?>
                                <div class="play-button">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M8 5v14l11-7z"></path>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <span class="no-preview">No preview yet</span>
                            <?php endif; ?>
                        </div>

                        <div class="song-info">

                            <h3 class="song-title"><?php echo htmlspecialchars($song["title"]); ?></h3>
                            <p class="artist"><?php echo htmlspecialchars($song["artist"]); ?></p>

                            <div class="meta">
                                <span><?php echo htmlspecialchars($song["album"]); ?></span>
                                <span class="genre"><?php echo htmlspecialchars($song["genre"]); ?></span>
                                <span class="language"><?php echo htmlspecialchars($song["language"]); ?></span>
                            </div>

                            <!-- REVIEWS -->
                            <div class="card-reviews">

                                <div class="rating-row">
                                    <span class="stars"></span>
                                    <span class="rating-text"></span>
                                </div>

                                <div class="mini-reviews"></div>

                                <button type="button" class="more-btn">Show all reviews</button>
                                <button type="button" class="add-btn">＋ Add your review</button>

                                <div class="mini-form" hidden>
                                    <input type="text" class="f-name" placeholder="Your name" maxlength="40">
                                    <div class="pick">
                                        <button type="button" data-value="1">★</button>
                                        <button type="button" data-value="2">★</button>
                                        <button type="button" data-value="3">★</button>
                                        <button type="button" data-value="4">★</button>
                                        <button type="button" data-value="5">★</button>
                                    </div>
                                    <textarea class="f-comment" rows="2" maxlength="300" placeholder="Write your review..."></textarea>
                                    <div class="f-error"></div>
                                    <button type="button" class="f-submit">Post Review</button>
                                </div>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <!-- EMPTY RESULT -->
            <div class="no-results" id="noResults">
                <h3>Nothing found</h3>
                <p>Try another song, artist, album, year, genre or language.</p>
            </div>

        </div>

    </section>

</main>


<!-- VIDEO MODAL -->
<div class="video-modal" id="videoModal">
    <div class="video-window">
        <div class="video-header">
            <div>
                <h3 id="modalTitle">Song Title</h3>
                <p id="modalArtist">Artist</p>
            </div>
            <button type="button" class="close-video" id="closeVideo">&times;</button>
        </div>
        <div class="video-area">
            <video id="musicVideo" controls playsinline></video>
        </div>
    </div>
</div>



<script>

/* =========================================================
   SOUND GROUP MUSIC LIBRARY JAVASCRIPT
   ========================================================= */

const searchInput    = document.getElementById("searchInput");
const artistFilter   = document.getElementById("artistFilter");
const albumFilter    = document.getElementById("albumFilter");
const yearFilter     = document.getElementById("yearFilter");
const genreFilter    = document.getElementById("genreFilter");
const languageFilter = document.getElementById("languageFilter");
const sortSelect     = document.getElementById("sortSelect");
const resetFilters   = document.getElementById("resetFilters");
const songsGrid      = document.getElementById("songsGrid");
const noResults      = document.getElementById("noResults");
const resultCount    = document.getElementById("resultCount");

const cards        = [...document.querySelectorAll(".song-card")];
const quickButtons = [...document.querySelectorAll(".quick-btn")];


/* =========================================================
   FILTER
   ========================================================= */

function filterSongs() {

    const search   = searchInput.value.toLowerCase().trim();
    const artist   = artistFilter.value.toLowerCase();
    const album    = albumFilter.value.toLowerCase();
    const year     = yearFilter.value;
    const genre    = genreFilter.value.toLowerCase();
    const language = languageFilter.value.toLowerCase();

    let visibleCount = 0;

    cards.forEach(card => {

        const searchableText =
            card.dataset.title + " " +
            card.dataset.artist + " " +
            card.dataset.album + " " +
            card.dataset.genre + " " +
            card.dataset.language;

        const visible =
            searchableText.includes(search) &&
            (artist   === "all" || card.dataset.artist   === artist)   &&
            (album    === "all" || card.dataset.album    === album)    &&
            (year     === "all" || card.dataset.year     === year)     &&
            (genre    === "all" || card.dataset.genre    === genre)    &&
            (language === "all" || card.dataset.language === language);

        card.style.display = visible ? "" : "none";
        if (visible) visibleCount++;

    });

    resultCount.textContent =
        "Showing " + visibleCount + " of " + cards.length + " items";

    noResults.style.display = visibleCount === 0 ? "block" : "none";
}

searchInput.addEventListener("input", filterSongs);

/* navbar search deep link: songs.php?q=term */
(function () {
    const q = new URLSearchParams(location.search).get("q");
    if (q) { searchInput.value = q; filterSongs(); searchInput.scrollIntoView({ block: "center" }); }
})();

[artistFilter, albumFilter, yearFilter, genreFilter, languageFilter]
    .forEach(select => select.addEventListener("change", filterSongs));


/* =========================================================
   QUICK LANGUAGE BUTTONS
   ========================================================= */

quickButtons.forEach(button => {
    button.addEventListener("click", function () {

        quickButtons.forEach(btn => btn.classList.remove("active"));
        this.classList.add("active");

        languageFilter.value = this.dataset.language;
        filterSongs();

    });
});


/* =========================================================
   RESET
   ========================================================= */

resetFilters.addEventListener("click", function () {

    searchInput.value    = "";
    artistFilter.value   = "all";
    albumFilter.value    = "all";
    yearFilter.value     = "all";
    genreFilter.value    = "all";
    languageFilter.value = "all";
    sortSelect.value     = "default";

    quickButtons.forEach(btn => btn.classList.remove("active"));
    quickButtons[0].classList.add("active");

    cards.forEach(card => {
        card.style.display = "";
        songsGrid.appendChild(card);
    });

    resultCount.textContent = "Showing " + cards.length + " items";
    noResults.style.display = "none";

});


/* =========================================================
   SORTING
   ========================================================= */

sortSelect.addEventListener("change", function () {

    const value = this.value;

    const sortedCards = [...cards].sort((a, b) => {

        if (value === "newest") return Number(b.dataset.year) - Number(a.dataset.year);
        if (value === "oldest") return Number(a.dataset.year) - Number(b.dataset.year);
        if (value === "az")     return a.dataset.title.localeCompare(b.dataset.title);
        if (value === "za")     return b.dataset.title.localeCompare(a.dataset.title);

        return 0;

    });

    sortedCards.forEach(card => songsGrid.appendChild(card));

    filterSongs();

});


/* =========================================================
   VIDEO PLAYER
   ========================================================= */

const videoModal  = document.getElementById("videoModal");
const musicVideo  = document.getElementById("musicVideo");
const modalTitle  = document.getElementById("modalTitle");
const modalArtist = document.getElementById("modalArtist");
const closeVideo  = document.getElementById("closeVideo");

cards.forEach(card => {

    card.addEventListener("click", function (e) {

        // Review area par click ho to video na khule
        if (e.target.closest(".card-reviews")) return;

        // no media uploaded yet - nothing to play (card shows "No preview yet")
        if (!this.dataset.video) return;

        openPlayer(this.dataset.songTitle, this.dataset.songArtist, this.dataset.video);

    });

});

function openPlayer(title, artist, src) {

    // stop the hero audio so two sounds never play together
    document.dispatchEvent(new CustomEvent("mh:pause"));

    modalTitle.textContent  = title;
    modalArtist.textContent = artist;

    musicVideo.src = src;

    videoModal.classList.add("active");

    musicVideo.play().catch(() => {});

}

// "Watch" button inside the hero
document.addEventListener("mh:watch", function (e) {
    openPlayer(e.detail.title, e.detail.artist, e.detail.video);
});

function closePlayer() {

    musicVideo.pause();
    musicVideo.currentTime = 0;
    musicVideo.removeAttribute("src");
    musicVideo.load();

    videoModal.classList.remove("active");

}

closeVideo.addEventListener("click", closePlayer);

videoModal.addEventListener("click", function (event) {
    if (event.target === videoModal) closePlayer();
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && videoModal.classList.contains("active")) {
        closePlayer();
    }
});


/* =========================================================
   REVIEWS (demo + user reviews)
   ========================================================= */

const DEMO_REVIEWS = <?php echo json_encode($demoReviews); ?>;
const STORAGE_KEY  = "soundgroup_user_reviews";
const AF_TS        = <?php echo time(); ?>;   // page render time (server throttles reviews)

let userReviews = {};
try {
    userReviews = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
} catch (e) {
    userReviews = {};
}

/* server stored reviews: slug -> [{name,rating,comment,date}]
   filled in by api/reviews.php (empty when the database is offline) */
let serverReviews = {};
fetch("api/reviews.php?action=list&scope=songs", { credentials: "same-origin" })
    .then(r => r.json())
    .then(d => {
        if (!d || !d.ok || !Array.isArray(d.reviews)) return;
        d.reviews.forEach(r => {
            if (!r.song_id) return;
            (serverReviews[r.song_id] = serverReviews[r.song_id] || []).push({
                name: r.name,
                rating: Number(r.rating),
                comment: r.comment,
                date: String(r.at || "").slice(0, 10)
            });
        });
        cards.forEach(c => renderCardReviews(c, false));   // repaint with server data
    })
    .catch(() => { /* offline: localStorage reviews still work */ });

function starsText(n) {
    n = Math.round(n);
    return "★".repeat(n) + "☆".repeat(5 - n);
}

function allReviews(id, slug) {
    // user ke reviews sab se upar, phir database wale, phir demo
    const mine = (userReviews[id] || []).map(r => ({ ...r, mine: true })).reverse();
    const srv  = (serverReviews[slug] || []).slice().reverse();
    const demo = DEMO_REVIEWS[id] || [];
    return [...mine, ...srv, ...demo];
}

function renderCardReviews(card, expanded) {

    const list = allReviews(card.dataset.id, card.dataset.saveId);
    const avg  = list.reduce((s, r) => s + r.rating, 0) / list.length;

    card.querySelector(".rating-row .stars").textContent = starsText(avg);
    card.querySelector(".rating-text").textContent =
        avg.toFixed(1) + " (" + list.length + " reviews)";

    const box = card.querySelector(".mini-reviews");
    box.innerHTML = "";

    const shown = expanded ? list : list.slice(0, 2);

    shown.forEach(r => {

        const item = document.createElement("div");
        item.className = "mini-review";

        const top = document.createElement("div");
        top.className = "mini-review-top";

        const left = document.createElement("span");
        const name = document.createElement("strong");
        name.textContent = r.name;
        left.appendChild(name);

        if (r.mine) {
            const tag = document.createElement("span");
            tag.className = "you";
            tag.textContent = "YOU";
            left.appendChild(tag);
        }

        const st = document.createElement("span");
        st.className = "stars";
        st.textContent = starsText(r.rating);

        top.append(left, st);

        const p = document.createElement("p");
        p.textContent = r.comment;

        item.append(top, p);
        box.appendChild(item);

    });

    const moreBtn = card.querySelector(".more-btn");
    moreBtn.style.display = list.length > 2 ? "" : "none";
    moreBtn.textContent = expanded ? "Show less" : "Show all " + list.length + " reviews";
}

cards.forEach(card => {

    let expanded = false;
    let rating   = 0;

    const form    = card.querySelector(".mini-form");
    const addBtn  = card.querySelector(".add-btn");
    const moreBtn = card.querySelector(".more-btn");
    const picks   = [...card.querySelectorAll(".pick button")];
    const nameIn  = card.querySelector(".f-name");
    const textIn  = card.querySelector(".f-comment");
    const errBox  = card.querySelector(".f-error");

    renderCardReviews(card, expanded);

    moreBtn.addEventListener("click", () => {
        expanded = !expanded;
        renderCardReviews(card, expanded);
    });

    addBtn.addEventListener("click", () => {
        form.hidden = !form.hidden;
        addBtn.textContent = form.hidden ? "＋ Add your review" : "✕ Cancel";
    });

    picks.forEach(b => b.addEventListener("click", () => {
        rating = Number(b.dataset.value);
        picks.forEach(x => x.classList.toggle("on", Number(x.dataset.value) <= rating));
    }));

    card.querySelector(".f-submit").addEventListener("click", async () => {

        const name    = nameIn.value.trim().slice(0, 40);
        const comment = textIn.value.trim().slice(0, 300);

        if (!name || !comment || rating < 1) {
            errBox.textContent = "Name, stars aur review likhna zaroori hai.";
            return;
        }

        const id   = card.dataset.id;
        const slug = card.dataset.saveId;      // same id as the save system
        const submitBtn = card.querySelector(".f-submit");
        submitBtn.classList.add("af-busy");
        submitBtn.disabled = true;

        /* ---- server first (api/reviews.php: validation + database) ---- */
        let stored = false;
        try {
            const r = await fetch("api/reviews.php?action=add", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": (document.querySelector("meta[name=af-csrf]") || { content: "" }).content
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    action: "add", song: slug, name: name, rating: rating,
                    comment: comment, improve: "", avatar: "",
                    ts: AF_TS, af_company: ""
                })
            });
            const d = await r.json().catch(() => ({}));
            if (d.ok) {
                stored = true;
                (serverReviews[slug] = serverReviews[slug] || []).push({
                    name: name, rating: rating, comment: comment,
                    date: new Date().toISOString().slice(0, 10)
                });
            } else if (r.status !== 503) {
                errBox.textContent = d.error || "Review save nahi ho saka - dobara try karein.";
            }
        } catch (e) { /* network down: localStorage fallback below */ }

        /* ---- fallback: review stays in this browser ---- */
        if (!stored) {
            if (!userReviews[id]) userReviews[id] = [];
            userReviews[id].push({
                name: name,
                rating: rating,
                comment: comment,
                date: new Date().toISOString().slice(0, 10)
            });
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(userReviews));
            } catch (e) {}
        }

        submitBtn.classList.remove("af-busy");
        submitBtn.disabled = false;
        errBox.textContent = "";
        nameIn.value = "";
        textIn.value = "";
        rating = 0;
        picks.forEach(x => x.classList.remove("on"));
        form.hidden = true;
        addBtn.textContent = "＋ Add your review";

        renderCardReviews(card, expanded);

    });

});


/* =========================================================
   INITIAL RESULT
   ========================================================= */

resultCount.textContent = "Showing " + cards.length + " items";

</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php require __DIR__ . '/includes/scripts.php'; ?>
