<?php

function normalize_bookmark($bookmark)
{
    $bookmark['url'] = trim($bookmark['url']);

    // validates URL
    $valid_protocols = ['ftp', 'http', 'https'];
    $bits = explode(':', $bookmark['url']);

    if (!in_array($bits[0], $valid_protocols)) {
        //discard the bookmark
        echo $bookmark['id'] . ' - ' . $bookmark['url'] . ' is not a valid URL, discarted' . "\n";
        return false;
    }

    // title2 is the custom title manuallly added by the user
    // which overrides the original bookmark title
    if (!is_null($bookmark['title2'])) {
        $bookmark['title'] = $bookmark['title2'];
    }

    unset($bookmark['title2']);

    
    $bookmark['title'] = trim($bookmark['title']);
    if (empty($bookmark['title'])) {
        $bookmark['title'] = 'Missing title';
    }

    // removes #tag_name from description
    $bits = explode(' ', str_replace('#', ' #', $bookmark['description']));
    $new_description = array();
    foreach ($bits as $bit) {
        if (strlen($bit) > 0 && $bit[0] !== '#') {
            $new_description[] = $bit;
        }
    }
    $bookmark['description'] = trim(implode(' ', $new_description));

    return $bookmark;
}

function is_duplicated_in_nextcloud($nextcloud_db, $bookmark)
{
    $sql = '
            SELECT 
                id
            FROM 
                oc_bookmarks
            WHERE
                url = "' . trim($bookmark['url']) . '"';

    $records = $nextcloud_db->query($sql);
    
    foreach ($records as $record) {
        echo $bookmark['url'] . ' is duplicated, discarted' . "\n";
        return true;
    }

    return false;
}

function get_tags($unmark_db, $bookmark)
{
    $tags = [];

    $sql = '
            SELECT 
                tags.name as tag_name
            FROM 
                tags, user_marks_to_tags
            WHERE
                tags.tag_id = user_marks_to_tags.tag_id
            AND
                user_marks_to_tags.users_to_mark_id=' . $bookmark['id'];
                        
    $records = $unmark_db->query($sql);

    foreach ($records as $tag) {
        array_push($tags, strtolower(trim($tag['tag_name'])));
    }

    return $tags;
}


function insert_bookmark_in_nextcloud($unmark_db, $bookmark)
{

    global $user, $pass, $user_id;
    $public = 0;

    $nextcloud_db = new PDO('mysql:host=localhost;dbname=nextcloud', $user, $pass);

    if (is_duplicated_in_nextcloud($nextcloud_db, $bookmark)) {
        return true;
    }
    
    $tags = get_tags($unmark_db, $bookmark);

    $sql = '
            INSERT INTO oc_bookmarks(
                url, title, description, added, lastmodified, user_id, public
            ) VALUES (
                :url, :title, :description, :added, :lastmodified, :user_id, :public
            )';

    $statement = $nextcloud_db->prepare($sql);

    $statement->bindParam(':url', $bookmark['url']);
    $statement->bindParam(':title', $bookmark['title']);
    $statement->bindParam(':description', $bookmark['description']);
    $statement->bindParam(':added', $bookmark['added']);
    $statement->bindParam(':lastmodified', $bookmark['lastmodified']);
    $statement->bindParam(':user_id', $user_id);
    $statement->bindParam(':public', $public);

    if (!$statement->execute()) {
        echo "Something went wrong with id: " . $bookmark['id'] . ' - ' . $bookmark['url'] . "\n";
    }

    if (count($tags)>0) {
        $last_id = $nextcloud_db->lastInsertId();
        foreach ($tags as $tag) {
            $sql = '
                INSERT INTO oc_bookmarks_tags(
                    bookmark_id, tag
                ) VALUES (
                    :bookmark_id, :tag
                )';
            $statement = $nextcloud_db->prepare($sql);
            $statement->bindParam(':bookmark_id', $last_id);
            $statement->bindParam(':tag', $tag);
            $statement->execute();
        }
    }
}
