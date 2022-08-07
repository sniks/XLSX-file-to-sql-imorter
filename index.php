<?php
$conn = new mysqli('localhost', 'nihal', 'Nihal@1234', 'importer') or die("Could not connect to mysql" . mysqli_error($con));

use Shuchkin\SimpleXLSX;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require_once __DIR__ . '/src/SimpleXLSX.php';

echo '<h1>XLSX to SQL</h1>';

if (isset($_FILES['file'])) {
    if ($xlsx = SimpleXLSX::parse($_FILES['file']['tmp_name'])) {
        echo '<h2>Upload Result</h2>';

        $dim = $xlsx->dimension();
        $cols = $dim[0];
        $f_row = $xlsx->rowsEx()[0];
        $key = array();
        $insert_keys = "";
        $name = explode(".", $_FILES['file']['name'])[0];
        $sql = "";
        foreach ($xlsx->readRows() as $k => $r) {
            $query = "";
            for ($i = 0; $i < $cols; $i++) {
                if ($k == 0) { // For creating table row dynamaiclly
                    $pattern = "/color: #FFFFFF/i";
                    //echo $f_row[$i]['css'];
                    if (preg_match($pattern, $f_row[$i]['css'])) { //Check red color value in css
                        $key[] = $i;

                        if ($r[$i] == 'date') {
                            $query .= str_replace(' ', '', $r[$i]) . " DATE";
                        } else {
                            $query .= str_replace(' ', '', $r[$i]) . " LONGTEXT";
                        }
                        $insert_keys .= str_replace(' ', '', $r[$i]);
                        if ($i < $cols - 2) {
                            $query .= ",";
                            $insert_keys .= ",";
                        }
                    }
                } else {
                    if (in_array($i, $key)) {
                        $query .= "'" . str_replace(' ', '', $r[$i]) . "'";
                        if ($i < $cols - 2) {
                            $query .= ",";
                        }
                    }
                }
            }
            if ($k == 0) {
                $sql_create = "CREATE TABLE " . $name . "(id INT NOT NULL PRIMARY KEY AUTO_INCREMENT," . $query . ")";
                if ($conn->query($sql_create)) {
                    echo "Table created successfully.";
                } else {
                    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
                    die;
                }
            } else {
                $sql .= "INSERT INTO " . $name . " (" . $insert_keys . ")VALUES (" . $query . ");";
            }
        }
        if ($conn->multi_query($sql) === TRUE) {
            echo "New records created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo SimpleXLSX::parseError();
    }
}
echo '<h2>Upload form</h2>
<form method="post" enctype="multipart/form-data">
*.XLSX <input type="file" name="file"  />&nbsp;&nbsp;<input type="submit" value="Upload To Database" />
</form>';
