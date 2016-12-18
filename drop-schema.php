<?php
/**
 * @author AnSick
 */

include 'properties.php';

$dest_db = mysqli_connect($dest_db_host, $dest_db_username, $dest_db_password, $dest_db_schema, $dest_db_port);

$schema_file_name = 'sql/drop-schema.sql';
$query = file_get_contents($schema_file_name);

echo mysqli_multi_query($dest_db, $query) ? "Success" : "Fail";

$dest_db->close();