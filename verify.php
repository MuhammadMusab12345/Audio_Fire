<?php
require __DIR__ . '/includes/db.php';
$r = $AF_DB->query("SELECT id,title,artist,artist_id,album,album_id,sort_order FROM songs WHERE id=129")->fetch(PDO::FETCH_ASSOC);
echo json_encode($r), PHP_EOL;
$next = $AF_DB->query("SELECT MAX(sort_order) m FROM songs")->fetchColumn();
echo "max_sort=$next", PHP_EOL;
$aid = $r['artist_id'] ?? 0;
$an = $AF_DB->query("SELECT name FROM artists WHERE id=".(int)$aid)->fetchColumn();
echo "artist table name=$an match=" . ($an === $r['artist'] ? 'YES' : 'NO'), PHP_EOL;
