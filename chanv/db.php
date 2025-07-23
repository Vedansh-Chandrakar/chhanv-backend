<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'chhanv';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
  
}
