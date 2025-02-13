<?php
function display_all_publications_by_year() {
    global $wpdb;
    $url_prefix = '/publications';
    $table_name = $wpdb->prefix . "research_publications";
    $limit_year = 2020;
    $years = $wpdb->get_col("SELECT DISTINCT year FROM $table_name ORDER BY year DESC");

    if(isset($_GET['publication-year']))
        $year = $_GET['publication-year'];
    else
        $year = null;
    if (!$year || !is_numeric($year)) {
        // Fetch all publications sorted by year
        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE year >= $limit_year ORDER BY year DESC");
        // Organize publications by year
        $grouped_publications = [];
        foreach ($results as $publication) {
            $year = intval($publication->year); // Ensure it's an integer for array indexing
            $grouped_publications[$year][] = $publication;
        }

        ?>
        <div class="available-years-div">
            <p>Available years:</p>
            <ul>
                <?php
                foreach ($years as $year) {
                    ?>
                    <li><a href="<?php echo '?publication-year=' . $year ?>"><?php echo $year ?></a></li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <div class="publications-wrapper-year">
        <?php

        foreach ($grouped_publications as $year => $publications) {

            ?>
            <div class="publications">
                <ul>
                <?php
                if($year < $limit_year)
                    break;
                echo "<h2 class='current-year'>$year</h2>";

                foreach ($publications as $publication) {
                    ?>
                    <li class='publication-text'>
                        <a href='<?php echo 'https://doi.org/' . $publication->doi ?>'> <?php echo  $publication->authors . ': ' . $publication->title . '. ' . $publication->forum . ', ' . $publication->pages . ' (' . $publication->year . ')' ?></a>
                        <p>Affiliated authors: <b><i><?php echo $publication->affiliated_authors ?></i></b></p>
                    </li>
                    <?php
                }
                ?>
                </ul>
            </div>
            <?php
            }
        ?>
        </div>
        <?php

        return;
    }

    $table_name = $wpdb->prefix . "research_publications";

    // Fetch publications for the specific year
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE year = %d", $year));

    if (empty($results)) {
        echo "No publications found for year $year.";
    } else {

        ?>
        <div class="available-years-div">
            <p>Available years:</p>
            <ul>
                <li><a href="<?php echo get_site_url() . '/publications' ?>">Latest</a></li>
                <?php
                foreach ($years as $year) {
                    ?>
                    <li><a href="<?php echo '?publication-year=' . $year ?>"><?php echo $year ?></a></li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <div class="publications">
            <ul>
                <h2 class='current-year'><?php echo $_GET['publication-year'] ?> </h2>
            <?php
            foreach ($results as $publication) {
                ?>
                <li class='publication-text'>
                    <a href='<?php echo 'https://doi.org/' . $publication->doi ?>'> <?php echo  $publication->authors . ': ' . $publication->title . '. ' . $publication->forum . ', ' . $publication->pages . ' (' . $publication->year . ')' ?></a>
                    <p>Affiliated authors: <b><i><?php echo $publication->affiliated_authors ?></i></b></p>
                </li>
                <?php
            }
            ?>
            </ul>
        </div>
        <?php
    }



}

