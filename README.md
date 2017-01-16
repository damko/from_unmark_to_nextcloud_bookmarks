# Migrate your bookmarks from Unmark to Nextcloud Bookmarks

This is a very basic PHP script written to migrate bookmarks from [Unmark](https://github.com/plainmade/unmark) to [Nextcloud Bookmarks](https://github.com/nextcloud/bookmarks).

It's a quite inefficient script but on my machine it parses about 1500 record per minutes which I suppose it's fast enough for most of the cases.

## How it works

It's a php script which runs from the command line and uses PDO to migrate records from database to database, including tags and customized bookmarks titles.

No additional libraries are required. If you run a common LAMP you should be ready to go. Works with php 5 or 7 out of the box.

## Usage

Backup your Nextcloud database:

	mysqldump -uroot -p nextcloud_db_name > ~/nextcloud-bck.sql

Create a MYSQL user (let's say "transfer") having right for both the Unmark and Nextcloud database:
	
	CREATE USER 'transfer'@'localhost' IDENTIFIED BY 'your_password_here';
	GRANT ALL PRIVILEGES ON unmark.* to 'transfer'@'localhost';
	GRANT ALL PRIVILEGES ON nextcloud.* to 'transfer'@'localhost';

Clone this repo, open a terminal and :
	
	cd <repo>

Edit the `transfer.php` file changing the credentials at the beginnig of the file.

Also edit `$user_id`. Be sure that this variable is set with your Nextcloud username.

Then run the script:

	php -f transfer.php

## Troubleshooting

If something goes wrong you can restore your Nexcloud db backup with this command:

	mysql -uroot -p nextcloud_db_name < ~/nextcloud-bck.sql

## Database structure

### Unmark

This is the Unmark database structure that I was running on my copy at the time of writing this script:

	+--------------------+
	| Tables_in_unmark   |
	+--------------------+
	| labels             |
	| marks              |
	| migrations         |
	| plain_sessions     |
	| tags               |
	| tokens             |
	| user_marks_to_tags |
	| users              |
	| users_to_marks     |
	+--------------------+

	mysql> describe marks;
	+-----------------+---------------------+------+-----+---------------------+-----------------------------+
	| Field           | Type                | Null | Key | Default             | Extra                       |
	+-----------------+---------------------+------+-----+---------------------+-----------------------------+
	| mark_id         | bigint(20) unsigned | NO   | PRI | NULL                | auto_increment              |
	| title           | varchar(150)        | NO   |     | NULL                |                             |
	| url             | text                | NO   |     | NULL                |                             |
	| url_key         | varchar(32)         | YES  | UNI | NULL                |                             |
	| embed           | text                | YES  |     | NULL                |                             |
	| embed_processed | tinyint(1)          | NO   |     | 0                   |                             |
	| created_on      | datetime            | NO   |     | 0000-00-00 00:00:00 |                             |
	| last_updated    | timestamp           | NO   |     | CURRENT_TIMESTAMP   | on update CURRENT_TIMESTAMP |
	+-----------------+---------------------+------+-----+---------------------+-----------------------------+

	mysql> describe tags;
	+--------+---------------------+------+-----+---------+----------------+
	| Field  | Type                | Null | Key | Default | Extra          |
	+--------+---------------------+------+-----+---------+----------------+
	| tag_id | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
	| name   | varchar(100)        | NO   | MUL | NULL    |                |
	| slug   | varchar(100)        | NO   | UNI | NULL    |                |
	+--------+---------------------+------+-----+---------+----------------+d

	mysql> describe user_marks_to_tags;
	+----------------------+---------------------+------+-----+---------+----------------+
	| Field                | Type                | Null | Key | Default | Extra          |
	+----------------------+---------------------+------+-----+---------+----------------+
	| user_marks_to_tag_id | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
	| tag_id               | bigint(20) unsigned | NO   | MUL | NULL    |                |
	| user_id              | bigint(20) unsigned | NO   | MUL | NULL    |                |
	| users_to_mark_id     | bigint(20) unsigned | NO   | MUL | NULL    |                |
	+----------------------+---------------------+------+-----+---------+----------------+

	mysql> describe users_to_marks;
	+------------------+---------------------+------+-----+---------------------+-----------------------------+
	| Field            | Type                | Null | Key | Default             | Extra                       |
	+------------------+---------------------+------+-----+---------------------+-----------------------------+
	| users_to_mark_id | bigint(20) unsigned | NO   | PRI | NULL                | auto_increment              |
	| mark_id          | bigint(20) unsigned | NO   | MUL | NULL                |                             |
	| mark_title       | text                | YES  |     | NULL                |                             |
	| user_id          | bigint(20) unsigned | NO   | MUL | NULL                |                             |
	| label_id         | bigint(20) unsigned | NO   | MUL | 1                   |                             |
	| notes            | text                | YES  |     | NULL                |                             |
	| active           | tinyint(1) unsigned | NO   |     | 1                   |                             |
	| created_on       | datetime            | NO   |     | 0000-00-00 00:00:00 |                             |
	| archived_on      | datetime            | YES  |     | NULL                |                             |
	| last_updated     | timestamp           | NO   |     | CURRENT_TIMESTAMP   | on update CURRENT_TIMESTAMP |
	+------------------+---------------------+------+-----+---------------------+-----------------------------+


### Nextcloud

This is the Nextcloud (version 11) database structure that I was running on my copy at the time of writing this script:

	mysql> describe oc_bookmarks;
	+--------------+------------------+------+-----+---------+----------------+
	| Field        | Type             | Null | Key | Default | Extra          |
	+--------------+------------------+------+-----+---------+----------------+
	| id           | int(11)          | NO   | PRI | NULL    | auto_increment |
	| url          | varchar(4096)    | NO   |     |         |                |
	| title        | varchar(4096)    | NO   |     |         |                |
	| user_id      | varchar(64)      | NO   |     |         |                |
	| description  | varchar(4096)    | NO   |     |         |                |
	| public       | smallint(6)      | YES  |     | 0       |                |
	| added        | int(10) unsigned | YES  |     | 0       |                |
	| lastmodified | int(10) unsigned | YES  |     | 0       |                |
	| clickcount   | int(10) unsigned | NO   |     | 0       |                |
	+--------------+------------------+------+-----+---------+----------------+

	mysql> describe oc_bookmarks_tags;
	+-------------+--------------+------+-----+---------+-------+
	| Field       | Type         | Null | Key | Default | Extra |
	+-------------+--------------+------+-----+---------+-------+
	| bookmark_id | bigint(20)   | YES  | MUL | NULL    |       |
	| tag         | varchar(255) | NO   |     |         |       |
	+-------------+--------------+------+-----+---------+-------+


**NOTE** if you changed the common database structure either for Unmark or Nextcloud Bookmarks you should modify the code in this script accordingly.