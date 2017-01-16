<?php


//database credentials
$user = 'transfer';
$pass = 'somepassword';

//nextcloud user_id
$user_id = 'nextcloud_username';

//connection to Unmark database
$unmark_db = new PDO('mysql:host=localhost;dbname=unmark', $user, $pass);

require_once 'functions.php';

$sql = '
        SELECT 
            users_to_marks.users_to_mark_id as id, 
            marks.url as url, 
            marks.title as title,
            users_to_marks.mark_title as title2,
            users_to_marks.notes as description,
            unix_timestamp(marks.created_on) as added,
            unix_timestamp(marks.last_updated ) as lastmodified
        FROM 
            marks, users_to_marks
        WHERE
            marks.mark_id = users_to_marks.mark_id
        ';

$bookmarks = $unmark_db->query($sql);

foreach ($bookmarks as $bookmark) {
    if (!$bookmark = normalize_bookmark($bookmark)) {
        continue;
    }

    insert_bookmark_in_nextcloud($unmark_db, $bookmark);
}
