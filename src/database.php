<head>
    <meta charset="UTF-8">
    <title>Movie Database</title>
    <link rel="stylesheet" type="text/css" href="dbstyle.css">
</head>
<body>
    <?php
    ini_set('display_errors', 'On');

    $mysqli = new mysqli("oniddb.cws.oregonstate.edu", "stryffem-db", "czOBEHllXrDenpeR", "stryffem-db");
    if(!$mysqli || $mysqli->connect_errno)
    {
        echo "Connection error" . $mysqli->connect_errno . " " . $mysqli->connect_error;
    }

    if(isset($_POST['name']))
    {
        //Following code adapted from PHP manual at http://us2.php.net/manual/en/mysqli.quickstart.prepared-statements.php
        //Prepare Statement
        if(!($stmt = $mysqli->prepare("INSERT INTO movies (name, category, length) VALUES (?, ?, ?)")))
            echo "Prepare failed: " . $mysqli->errno . " " . $mysqli->error;
        
        //Bind
        if(!($stmt->bind_param("ssi", $_POST['name'], $_POST['category'], $_POST['length'])))
            echo "Binding parameters failed: " . $stmt->errno . " " . $stmt->error;
        
        //Execute
        if(!($stmt->execute()))
            echo "Execute failed: " . $stmt->errno . " " . $stmt->error;
        
        unset($_POST['name']);
    }

    if(isset($_POST['rented']))
    {
        $setChecked = 0;
         $_POST['check'] = urldecode($_POST['check']);
        if($_POST['rented'] == "Available")
           $setChecked = 1;
        if(!($mysqli->query("UPDATE movies SET rented = '$setChecked' WHERE name = '$_POST[check]'")))
            echo "Update failed.";
    }

    if(isset($_POST['delete']))
    {
        $_POST['delete'] = urldecode($_POST['delete']);
        if(!($mysqli->query("DELETE FROM movies WHERE name = '$_POST[delete]'")))
            echo "Delete failed.";
    }

    if(isset($_POST['deleteAll']))
    {
        if(!($mysqli->query("DELETE FROM movies")))
            echo "Delete Failed.";
    }

    
    ?>
    
    <!---------- ADD MOVIE FORM ----------->
    
    <form action="database.php" method="POST">
    <p>
    Movie Name: <input type="text" name="name" required>
    Category: <input type="text" name="category">
    Length: <input type="number" name="length" min="1">
    <input type="submit" value="Add Movie">
    </p>
    </form>
    
    
    <!---------- SELECT CATEGORY FORM ----------->
    <?php
        echo "<form action='database.php' method='GET'>";
        echo "Select Category <select name='myCategory'>";
        echo "<option>All Movies</option>";
        
        $outCategory = NULL;

        if(!($catStmt = $mysqli->prepare("SELECT DISTINCT category FROM movies")))
            echo "Prepare failed: " . $mysqli->errno . " " . $mysqli->error;

        if(!($catStmt->execute()))
            echo "Execute failed: "  . $catStmt->errno . " " . $catStmt->error;
    
        if(!($catStmt->bind_result($outCategory)))
            echo "Binding output parameters failed: " . $catStmt->errno . " " . $catStmt->error;

        while($catStmt->fetch())
        {
            if($outCategory != NULL)
                echo "<option>" . $outCategory . "</option>";
        }

        $catStmt->close();
    ?>
        </select>
        <input type="submit" value="Filter">
        
    </form>
        
        
    <!---------- OUTPUT TABLE FORM ----------->
    <?php
    $urlAppend = NULL;
    echo "<table>";
    echo "<tr><td>Name</td><td>Category</td><td>Length</td><td>Checked Out</td><td></td></tr>";
        
            if(!($resultStmt = $mysqli->prepare("SELECT name, category, length, rented FROM movies WHERE category LIKE ?")))
                echo "Prepare failed: " . $mysqli->errno . " " . $mysqli->error;
            
            $catSelect = NULL;
            if(isset($_GET['myCategory']) && $_GET['myCategory'] != "All Movies")
            {
                $catSelect = $_GET['myCategory'];
            }
            else
                $catSelect = '%';

            if(!($resultStmt->bind_param("s", $catSelect)))
                echo "Binding parameters failed: " . $resultStmt->errno . " " . $resultStmt->error;

            if(!($resultStmt->execute()))
                echo "Execute failed: "  . $resultStmt->errno . " " . $resultStmt->error;

            $outName = NULL;
            $outCategory = NULL;
            $outLength = NULL;
            $outRented = NULL;

            if(!($resultStmt->bind_result($outName, $outCategory, $outLength, $outRented)))
                echo "Binding output parameters failed: " . $resultStmt->errno . " " . $resultStmt->error;

            while($resultStmt->fetch())
            {
                if($outRented == 0)
                    $outRented = "Available";
                else
                    $outRented = "Checked Out";
                if($outLength == 0)
                    $outLength = "";
                echo "<tr><td>" . $outName . "</td><td>" . $outCategory . "</td><td>" . $outLength . "</td>";
                
                $outName = urlencode($outName);
                
                //Code adapted from hidden value tutorial at http://www.echoecho.com/htmlforms07.htm
                echo "<td>" . $outRented . "  <form action='database.php' method='POST'><input type='hidden' name='check' value=" . $outName . ">";
                echo "<input type='hidden' name='rented' value=" . $outRented . "><input type='submit' value='Check In/Out'></form></td>";
                echo "<td><form action='database.php' method='POST'><input type='hidden' name='delete' value=" . $outName . "><input type='submit' value='Delete'></form></td></tr>";
            }
            $resultStmt->close();
        ?>
    </table>

    <form action='database.php' method='POST'>
        <input type='hidden' name='deleteAll' value='deleteAll'>
        <input type='submit' value='Delete All Movies'>
    </form>
</body>