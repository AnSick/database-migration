<?php
/**
 * @author AnSick
 */

include 'properties.php';
include 'Logger.php';

$log = new Logger('migration_log.txt', Logger::debug);

// "2016-12-12 00:00:00"
$date_format = '%Y-%m-%d %h:%i:%s';
$current_date = date(time());

class Film {
    public $title;
    public $description;
    public $release_year;
    public $rental_price;
    public $category;
}

function execute_query(\mysqli $database, $query_string) {
    global $log;
    $log->debug('Query: ' . $query_string);

    $query = mysqli_query($database, $query_string);

    if (!$query) {
        $mysqli_error = mysqli_error($database);
        $log->error($mysqli_error);
        exit('MySQL Error! ' . $mysqli_error . PHP_EOL);
    };

    return $query;
}

function migrate_categories($src_db, $dest_db) {
    global $current_date;

    $query_string = "SELECT name FROM category";

    $new_categories = [];
    $query = execute_query($src_db, $query_string);
    while ($category = $query->fetch_array()) {
        $new_categories[] = $category;
    }

    $old_categories = [];
    $query = execute_query($dest_db, $query_string);
    while ($category = $query->fetch_array()) {
        $old_categories[] = $category;
    }

    foreach ($new_categories as $new_category) {
        $found = false;
        foreach ($old_categories as $old_category) {
            if ($new_category['name'] == $old_category['name']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $name = $new_category['name'];
            execute_query($dest_db, "INSERT INTO category(name, migration_date) VALUES ('$name', sysdate())");
        }
    }
}

function migrate_rental_price($src_db, $dest_db, \Film $film) {
    global $current_date;

    $query = execute_query($dest_db, "
        SELECT amount
        FROM rental_price
        WHERE migration_date = (SELECT max(migration_date) FROM rental_price WHERE film_title = '$film->title')
    ");
    $query_object = $query ? $query->fetch_object() : false;
    $old_rental_price = $query_object ? $query_object->amount : -1;

    $query = execute_query($src_db, "SELECT rental_rate FROM film WHERE title = '$film->title'");
    $current_rental_price = $query->fetch_object()->rental_rate;

    if ($current_rental_price != $old_rental_price) {
        execute_query($dest_db, "
            INSERT INTO rental_price(film_title, amount, migration_date)
            VALUE ('$film->title', $current_rental_price, sysdate())
        ");
    }
}

function migrate_rent($src_db, $dest_db, \Film $film) {
    global $current_date, $date_format;

    $query = execute_query($src_db, "
        SELECT
          rental.rental_date AS start_date,
          rental.return_date AS end_date
        FROM rental
          JOIN inventory ON rental.inventory_id = inventory.inventory_id
          JOIN film ON inventory.film_id = film.film_id
        WHERE film.film_id = (SELECT film_id FROM film WHERE title = '$film->title')
        ORDER BY rental_id
    ");

    while ($row = $query->fetch_array()) {
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];

        execute_query($dest_db, "
            INSERT INTO rent (film_title, start_date, end_date, migration_date)
            VALUES ('$film->title', str_to_date('$start_date', '$date_format'), str_to_date('$end_date', '$date_format'), sysdate())
        ");
    }
}

function migrate_film($src_db, $dest_db, \Film $film) {
    $found = execute_query($dest_db, "SELECT COUNT(title) AS count FROM film WHERE title = '$film->title'");

    if ($found->fetch_object()->count == 0) {
        migrate_rental_price($src_db, $dest_db, $film);
        migrate_rent($src_db, $dest_db, $film);

        execute_query($dest_db, "
            INSERT INTO film(
                title,
                description,
                category_id,
                release_year,
                rental_price_id,
                rent_id
            ) VALUES (
                '$film->title',
                '$film->description',
                (SELECT id FROM category WHERE name = '$film->category'),
                year('$film->release_year'),
                (SELECT id from rental_price WHERE film_title = '$film->title' AND migration_date = sysdate()),
                (SELECT id FROM rent WHERE film_title = '$film->title' AND migration_date = sysdate())
            )
        ");
    }
}

function migrate_films($src_db, $dest_db) {
    $films = [];
    $query = execute_query($src_db, '
        SELECT
          title,
          description,
          release_year,
          rental_rate,
          category.name AS category
        FROM film
          JOIN film_category ON film.film_id = film_category.film_id
          JOIN category ON film_category.category_id = category.category_id
        ORDER BY title;
    ');

    while ($row = $query->fetch_array()) {
        $film = new Film();
        $film->title = $row['title'];
        $film->description = $row['description'];
        $film->release_year = $row['release_year'];
        $film->rental_price = $row['rental_rate'];
        $film->category = $row['category'];

        $films[] = $film;
    }

    foreach ($films as $film) {
        migrate_film($src_db, $dest_db, $film);
    }
}

$src_db = mysqli_connect($src_db_host, $src_db_username, $src_db_password, $src_db_schema, $src_db_port);
$dest_db = mysqli_connect($dest_db_host, $dest_db_username, $dest_db_password, $dest_db_schema, $dest_db_port);

migrate_categories($src_db, $dest_db);
migrate_films($src_db, $dest_db);

$src_db->close();
$dest_db->close();