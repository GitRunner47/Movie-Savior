<?php
require_once(__DIR__ . "/../../../lib/app.php");
require_role("Admin");

$errors = [];
$title = "";
$overview = "";
$show_type = "both";
$genres = [];
$creators = [];
$cast = [];
$images = [];
$services = [];
$action = "";
if (isset($_POST["action"]) && is_string($_POST["action"])) {
    require_csrf_token(project_url("admin/create_movie.php"));
    $action = $_POST["action"];
}
$active_form = "api";
if ($action === "create_movie") {
    // Keep the manual form visible when its validation returns an error.
    $active_form = "manual";
}
if (isset($_POST["title"]) && is_string($_POST["title"]) && trim($_POST["title"]) !== "") {
    $title = trim($_POST["title"]);
}
if (isset($_POST["show_type"]) && is_string($_POST["show_type"]) && trim($_POST["show_type"]) !== "") {
    $show_type = $_POST["show_type"];
}

if (isset($_GET['method']) && $_GET['method'] === "manual") {
    $active_form = "manual";
} else if (isset($_GET['method']) && $_GET['method'] === "api") {
    $active_form = "api";
}

if ($action == "import_movies") {
    try {
        $show_types = ["movie", "series", "both"];
        if (!in_array($show_type, $show_types, true)) {
            $errors[] = "Choose a supported show type.";
        }

        $movies = search_shows($title, $show_type, $errors);
        $movies_insert = [];
        $duplicate_count = 0;
        $result_rows = 0;
        if (empty($errors) && !empty($movies)) {
            foreach($movies as $movie) {
                $query = "SELECT * FROM MOVIES WHERE api_id = :api_id LIMIT 1";
                $stmt = select($query, [":api_id" => $movie['api_id']]);
                if (!empty($stmt)) {
                    $duplicate_count++;
                }
                $movies_insert = [
                    "title" => $movie['title'], 
                    "overview" => $movie['overview'],
                    "show_type" => $movie['show_type'],
                    "api_id" => $movie['api_id']
                ];

                if (!empty($movies_insert)) {
                    
                    $query = "SELECT * FROM MOVIES WHERE api_id IS NOT NULL AND api_id = :api_id LIMIT 1";
                    $duplicate = select($query, [":api_id" => $movie['api_id']]);
                
                    $result = [];
                    $movie_id = null;
                    if (!empty($duplicate)) {
                        $result = update("MOVIES", [
                            "id" => $duplicate['id'],
                            "title" => $movie['title'],
                            "overview" => $movie['overview'], 
                            "show_type" => $movie['show_type'],
                            "is_api" => 1
                        ]);
                        $movie_id = $duplicate['id'];
                    } else {
                        $result = insert("MOVIES", $movies_insert, [
                            "update-duplicate" => true,
                            "columns to update" => [
                                "title", "overview", "show_type"
                            ]
                        ]);
                        $result_rows++;
                        $movie_id = $result['lastInsertId'];            
                    }
                }
                foreach($movie["genres"] as $genre) {
                    if ($genre === null) {
                        continue;
                    }
                    $query = "SELECT id AS genre_id FROM Genre WHERE genre = :genre LIMIT 1";
                    $stmt = select($query, [":genre" => $genre['name']]);
                    $genre_id = NULL;
                    if (empty($stmt)) {
                        $result = insert("Genre", ["genre" => $genre['name']]);
                        $genre_id = $result['lastInsertId'];
                    }
                    else {
                        $genre_id = $stmt['genre_id'];
                    }
                    $temp_insert = ["movie_id" => $movie_id, "genre_id" => $genre_id];

                    $query = "SELECT movie_id, genre_id FROM MovieGenres WHERE movie_id = :movie_id AND genre_id = :genre_id LIMIT 1";
                    $stmt = select($query, [":movie_id" => $movie_id, ":genre_id" => $genre_id]);
                    if (!empty($stmt)) {
                        continue;
                    }
                    $temp_result = insert("MovieGenres", $temp_insert);
                }

                foreach($movie["creators"] as $creator) {
                    if ($creator === null) {
                        continue;
                    }
                    $query = "SELECT id AS creator_id FROM Creators WHERE creator = :creator LIMIT 1";
                    $stmt = select($query, [":creator" => $creator]);
                    $creator_id = NULL;
                    if (empty($stmt)) {
                        $result = insert("Creators", ["creator" => $creator]);
                        $creator_id = $result['lastInsertId'];
                    } else {
                        $creator_id = $stmt['creator_id'];
                    }
                    $temp_insert = ["movie_id" => $movie_id, "creator_id" => $creator_id];
                    $query = "SELECT movie_id, creator_id FROM MovieCreators WHERE movie_id = :movie_id AND creator_id = :creator_id LIMIT 1";
                    $stmt = select($query, [":movie_id" => $movie_id, ":creator_id" => $creator_id]);
                    if (!empty($stmt)) {
                        continue;
                    }
                    $temp_result = insert("MovieCreators", $temp_insert);
                }

                foreach($movie["cast"] as $member) {
                    if ($member === null) {
                        continue;
                    }
                    $query = "SELECT id AS member_id FROM Cast WHERE member = :member LIMIT 1";
                    $stmt = select($query, [":member" => $member]);
                    $member_id = NULL;
                    if (empty($stmt)) {

                        $result = insert("Cast", ["member" => $member]);
                        $member_id = $result['lastInsertId'];
                    }
                    else {
                        $member_id = $stmt['member_id'];
                    }
                    $temp_insert = ["movie_id" => $movie_id, "member_id" => $member_id];

                    $query = "SELECT movie_id, member_id FROM MovieCast WHERE movie_id = :movie_id AND member_id = :member_id LIMIT 1";
                    $stmt = select($query, [":movie_id" => $movie_id, ":member_id" => $member_id]);
                    if (!empty($stmt)) {
                        continue;
                    }

                    $temp_result = insert("MovieCast", $temp_insert);
                }

                $images = $movie["images"];
                if ($images === null) {
                    continue;
                }
                $horizontalType = $images["horizontalPoster"];
                $horizontal = $horizontalType["w720"];
                $verticalType = $images["verticalPoster"];
                $vertical = $verticalType["w480"];
                if (!$horizontal || !$vertical) {
                    continue;
                }

                $query = "SELECT id AS image_id FROM Images WHERE imageAddr = :image AND ImageType = :Horizontal LIMIT 1";
                $stmt_horizontal = select($query, [":image" => $horizontal, ":Horizontal" => "Horizontal"]);
                $query = "SELECT id AS image_id FROM Images WHERE imageAddr = :image AND ImageType = :Vertical LIMIT 1";
                $stmt_vertical = select($query, [":image" => $vertical, ":Vertical" => "Vertical"]);
                $horizontal_image_id = NULL;
                $vertical_image_id = NULL;
                if (empty($stmt_horizontal)) {
                    $result_horizontal = insert("Images", ["ImageType" => "Horizontal", "ImageAddr" => $horizontal]);
                    $horizontal_image_id = $result_horizontal['lastInsertId'];
                } else {
                    $horizontal_image_id = $stmt_horizontal['image_id'];
                }
                if (empty($stmt_vertical)) {
                    $result_vertical = insert("Images", ["ImageType" => "Vertical", "ImageAddr" => $vertical]);
                    $vertical_image_id = $result_vertical['lastInsertId'];
                } else {
                    $vertical_image_id = $stmt_vertical['image_id'];
                }

                $temp_insert = ["movie_id" => $movie_id, "image_id" => $horizontal_image_id];

                $query = "SELECT movie_id, image_id FROM MovieImages WHERE movie_id = :movie_id AND image_id = :image_id LIMIT 1";
                $stmt = select($query, [":movie_id" => $movie_id, ":image_id" => $horizontal_image_id]);
                if (empty($stmt)) {
                    insert("MovieImages", $temp_insert);                    
                }

                $temp_insert = ["movie_id" => $movie_id, "image_id" => $vertical_image_id];

                $query = "SELECT movie_id, image_id FROM MovieImages WHERE movie_id = :movie_id AND image_id = :image_id LIMIT 1";
                $stmt = select($query, [":movie_id" => $movie_id, ":image_id" => $vertical_image_id]);
                if (empty($stmt)) {
                    insert("MovieImages", $temp_insert);
                }

                foreach($movie["services"] as $serviceType) {
                    if ($serviceType === null) {
                        continue;
                    }
                    $serviceSet = $serviceType['service'];
                    $service = $serviceSet['name'];

                    $query = "SELECT id AS service_id FROM Services WHERE service = :service LIMIT 1";
                    $stmt = select($query, [":service" => $service]);
                    $service_id = NULL;
                    if (empty($stmt)) {
                        $result = insert("Services", ["Service" => $service]);
                        $service_id = $result['lastInsertId'];
                    } else {
                        $service_id = $stmt['service_id'];
                    }

                    $temp_insert = ["movie_id" => $movie_id, "service_id" => $service_id];

                    $query = "SELECT movie_id, service_id FROM MovieServices WHERE movie_id = :movie_id AND service_id = :service_id LIMIT 1";
                    $stmt = select($query, [":movie_id" => $movie_id, ":service_id" => $service_id]);
                    if (!empty($stmt)) {
                        continue;
                    }
                    insert("MovieServices", $temp_insert);
                }
            }
            if ($duplicate_count > 0) {
                flash("Found " . $duplicate_count . " duplicate row(s)", "warning");
            }
            flash($result_rows . " API movie row(s) saved.", "success");
            header("Location: " . project_url("admin/create_movie.php"));
            exit;
        }
    }
    catch (Throwable $e) {
        error_log("Movie import failed on line " . $e->getLine() . ": " . $e->getMessage());
        $errors[] = "The movie or show could not be imported.";
    }
}

if ($action === "create_movie") {
    $show_types = ["movie", "series"];
    if (!in_array($show_type, $show_types, true)) {
        $errors[] = "Choose a supported show type.";
    }
    if (isset($_POST['title']) && is_string($_POST['title']) && trim($_POST['title']) !== "" && strlen($_POST['title']) <= 100) {
        $title = trim($_POST["title"]);
    }
    if (isset($_POST['show_type']) && is_string($_POST['show_type']) && trim($_POST['show_type']) !== "" && strlen($_POST['show_type']) <= 10) {
        $show_type = $_POST["show_type"];
    }
    if (isset($_POST['overview']) && is_string($_POST['overview']) && strlen($_POST['overview']) <= 500000) {
        $overview = trim($_POST["overview"]);
    }
    if (isset($_POST["genre"]) && is_array($_POST["genre"])) {
        foreach ($_POST['genre'] as $genre) {
            if (trim($genre) === "" || !is_string($genre)) {
                continue;
            }
            if (strlen($genre) > 20) {
                $errors[] = "Genre input more than 20 characters.";
                continue;
            }
            $genres[] = trim($genre);
        }
    }
    if (isset($_POST["creator"]) && is_array($_POST["creator"])) {
        foreach($_POST['creator'] as $creator) {
            if (trim($creator) === "" || !is_string($creator)) {
                continue;
            }
            if (strlen($creator) > 50) {
                $errors[] = "Creator input more than 50 characters.";
                continue;
            }
            $creators[] = trim($creator);
        }
    }
    if (isset($_POST['cast']) && is_array($_POST['cast'])) {
        foreach($_POST['cast'] as $member) {
            if (trim($member) === "" || !is_string($member)) {
                continue;
            }
            if (strlen($member) > 50) {
                $errors[] = "Cast input more than 50 characters.";
                continue;
            }
            $cast[] = $member;
        }
    }
    if (isset($_POST['service']) && is_array($_POST['service'])) {
        foreach($_POST['service'] as $service) {
            if (trim($service) === "" || !is_string($service)) {
                continue;
            }
            if (strlen($service) > 20) {
                $errors[] = "Service input more than 20 characters.";
                continue;
            }
            $services[] = $service;
        }
    }
    if (empty($errors)) {
        try {
            $movies_insert = [
                "api_id" => NULL,
                "title" => $title,
                "overview" => $overview,
                "show_type" => $show_type,
                "is_api" => 0
            ];
            $query = "SELECT * FROM MOVIES WHERE title = :title AND is_api = 0 LIMIT 1";
            $duplicate = select($query, [":title" => $title]);

            $result = [];
            $id_temp = "";
            if (!empty($duplicate)) {
                $result = update("MOVIES", [ 
                    "title" => $title, 
                    "overview" => $overview, 
                    "show_type" => $show_type,
                    "is_api" => 0
                ],
                [
                    "title",
                    "is_api"
                ]);
                $id_temp = $duplicate["id"];
            } else {
                $result = insert("MOVIES", $movies_insert);
                $id_temp = $result['lastInsertId'];
            }
            

            $movie_id = $id_temp;

            foreach($genres as $genre) {
                if (!$genre) {
                    continue;
                }
                $query = "SELECT id AS genre_id FROM Genre WHERE genre = :genre LIMIT 1";
                $stmt = select($query, [":genre" => $genre]);
                $genre_id = NULL;
                if (empty($stmt)) {
                    $result = insert("Genre", ["genre" => $genre]);
                    $genre_id = $result['lastInsertId'];
                }
                else {
                    $genre_id = $stmt['genre_id'];
                }
                $temp_insert = ["movie_id" => $movie_id, "genre_id" => $genre_id];

                $query = "SELECT movie_id, genre_id FROM MovieGenres WHERE movie_id = :movie_id AND genre_id = :genre_id LIMIT 1";
                $stmt = select($query, [":movie_id" => $movie_id, ":genre_id" => $genre_id]);
                if (!empty($stmt)) {
                    continue;
                }
                $temp_result = insert("MovieGenres", $temp_insert);
            }

            foreach($creators as $creator) {
                if (!$creator) {
                    continue;
                }
                $query = "SELECT id AS creator_id FROM Creators WHERE creator = :creator LIMIT 1";
                $stmt = select($query, [":creator" => $creator]);
                $creator_id = NULL;
                if (empty($stmt)) {
                    $result = insert("Creators", ["creator" => $creator]);
                    $creator_id = $result['lastInsertId'];
                } else {
                    $creator_id = $stmt['creator_id'];
                }
                $temp_insert = ["movie_id" => $movie_id, "creator_id" => $creator_id];

                $query = "SELECT movie_id, creator_id FROM MovieCreators WHERE movie_id = :movie_id AND creator_id = :creator_id LIMIT 1";
                $stmt = select($query, [":movie_id" => $movie_id, ":creator_id" => $creator_id]);
                if (!empty($stmt)) {
                    continue;
                }
                insert("MovieCreators", $temp_insert);
            }

            foreach($cast as $member) {
                if (!$member) {
                    continue;
                }
                $query = "SELECT id AS member_id FROM Cast WHERE member = :member LIMIT 1";
                $stmt = select($query, [":member" => $member]);
                $member_id = NULL;
                if (empty($stmt)) {
                    $result = insert("Cast", ["member" => $member]);
                    $member_id = $result['lastInsertId'];
                } else {
                    $member_id = $stmt['member_id'];
                }
                $temp_insert = ["movie_id" => $movie_id, "member_id" => $member_id];
                
                $query = "SELECT * FROM MovieCast WHERE movie_id = :movie_id AND member_id = :member_id LIMIT 1";
                $stmt = select($query, [":movie_id" => $movie_id, ":member_id" => $member_id]);
                if (!empty($stmt)) {
                    continue;
                }
                insert("MovieCast", $temp_insert);
            }

            foreach($services as $service) {
                    if (!$service) {
                    continue;
                }
                $query = "SELECT id AS service_id FROM Services WHERE service = :service LIMIT 1";
                $stmt = select($query, [":service" => $service]);
                $service_id = NULL;
                if (empty($stmt)) {
                    $result = insert("Services", ["service" => $service]);
                    $service_id = $result['lastInsertId'];
                } else {
                    $service_id = $stmt['service_id'];
                }
                $temp_insert = ["movie_id" => $movie_id, "service_id" => $service_id];
                
                $query = "SELECT * FROM MovieServices WHERE movie_id = :movie_id AND service_id = :service_id LIMIT 1";
                $stmt = select($query, [":movie_id" => $movie_id, ":service_id" => $service_id]);
                if (!empty($stmt)) {
                    continue;
                }
                insert("MovieServices", $temp_insert);
            }
            flash_errors($errors);
            flash("Movie or show created.", "success");
            header("Location: " . project_url("admin/create_movie.php"));
            exit;
        }
        catch (Throwable $e) {
            error_log("Manual movie creation failed: " . $e->getMessage());
            $errors[] = "The movie could not be created.";
        }
    }
}

flash_errors($errors);
?>

<!doctype html>
<html lang="en">
<head>
    <?php render_head("Create Movie or Show Entry"); ?>
</head>
<body>
    <?php render_nav(); ?>
    <main class="container py-4">
        <h1>Create Movie or Show</h1>
<div class="btn-group mb-4" role="group" aria-label="Movie creation method">
    <button
        class="btn btn-outline-primary"
        type="button"
        data-movie-form="api"
        aria-controls="MovieApiForm"
    >
        Import From API
    </button>
    <button
        class="btn btn-outline-primary"
        type="button"
        data-movie-form="manual"
        aria-controls="MovieManualForm"
    >
        Create Manually
    </button>
</div>

<div class="row g-4">
    <section
        id="MovieApiForm"
        class="col-12"
        <?php if ($active_form !== "api"): ?>hidden<?php endif; ?>
    >
        <h2>Import API Movies and Shows</h2>
        <p>Call the search title api for a number of movies or shows and update matching API rows.</p>
        <form method="post">
            <?php
            render_csrf_input(); // New line.
            render_input([
                "label" => "Select movie or show",
                "type" => "select",
                "name" => "show_type",
                "value" => $show_type,
                "options" => ["movie" => "Movie", "series" => "Show", "both" => "Both"],
            ]);
            render_input([
                "label" => "Enter title",
                "type" => "text",
                "name" => "title",
                "attributes" => ["required" => true],
            ]);
            render_button([
                "text" => "Import API Movies",
                "variant" => "success",
                "attributes" => ["name" => "action", "value" => "import_movies"],
            ]);
            ?>
        </form>
    </section>

    <section
        id="MovieManualForm"
        class="col-12"
        <?php if ($active_form !== "manual"): ?>hidden<?php endif; ?>
    >
        <h2>Create Manual Movie or Show</h2>
        <form method="post">
            <?php
            render_csrf_input(); // New line.
            render_input([
                "label" => "Select movie or show",
                "type" => "select",
                "name" => "show_type",
                "value" => "movie",
                "options" => ["movie" => "Movie", "series" => "Show"],
            ]);
            render_input([
                "label" => "Enter Title",
                "type" => "text",
                "name" => "title",
                "attributes" => ["required" => true],
            ]);
            render_input([
                "label" => "Overview",
                "type" => "textarea",
                "name" => "overview",
            ]);
            ?>
        <div id="genres">
            <?php
            render_input([
                "label" => "Add Genre",
                "type" => "text",
                "name" => "genre[]",
                "attributes" => ["maxlength" => 20],
            ]);
            ?>
        </div>
            <?php
            render_button([
                "text" => "Add",
                "type" => "button",
                "variant" => "primary",
                "attributes" => ["name" => "genre-btn"]
            ]);
            ?>
        <div id="creators">
            <?php
            render_input([
                "label" => "Add Creator",
                "type" => "text",
                "name" => "creator[]",
                "attributes" => ["maxlength" => 50],
            ]);
            ?>
        </div>
            <?php
            render_button([
                "text" => "Add",
                "type" => "button",
                "variant" => "primary",
                "attributes" => ["name" => "creator-btn"]
            ]);
            ?>
        <div id="cast">
            <?php
            render_input([
                "label" => "Add Cast Member",
                "type" => "text",
                "name" => "cast[]",
                "attributes" => ["maxlength" => 50],
            ]);
            ?>
        </div>
            <?php
            render_button([
                "text" => "Add",
                "type" => "button",
                "variant" => "primary",
                "attributes" => ["name" => "cast-btn"]
            ]);
            ?>
        <div id="services">
            <?php
            render_input([
                "label" => "Add Service",
                "type" => "text",
                "name" => "service[]",
                "attributes" => ["maxlength" => 50],
            ]);
            ?>
        </div>
            <?php
            render_button([
                "text" => "Add",
                "type" => "button",
                "variant" => "primary",
                "attributes" => ["name" => "service-btn"]
            ]);
            render_button([
                "text" => "Reset",
                "type" => "reset",
                "variant" => "secondary",
                "attributes" => ["class" => "col-3"]
            ]);
            render_button([
                "text" => "Create",
                "variant" => "success",
                "attributes" => ["name" => "action", "value" => "create_movie", "class" => "col-3"]
            ]);
            ?>
        </form>
    </section>
</div>

<script>
    const apiMovieForm = document.querySelector("#MovieApiForm");
    const manualMovieForm = document.querySelector("#MovieManualForm");
    const MovieFormButtons = document.querySelectorAll("[data-movie-form]");

    const genrebtn = document.querySelector('button[name="genre-btn"]');
    const creatorbtn = document.querySelector('button[name="creator-btn"]');
    const castbtn = document.querySelector('button[name="cast-btn"]');
    const servicebtn = document.querySelector('button[name="service-btn"]');

    const genrecontainer = document.getElementById('genres');
    const creatorcontainer = document.getElementById('creators');
    const castcontainer = document.getElementById('cast');
    const servicecontainer = document.getElementById('services');


    genrebtn.addEventListener("click", () => {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'genre[]';
            input.className = 'form-control';
            genrecontainer.appendChild(input);
        });
    creatorbtn.addEventListener("click", () => {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'creator[]';
            input.className = 'form-control';
            creatorcontainer.appendChild(input);
        });
    castbtn.addEventListener("click", () => {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'cast[]';
            input.className = 'form-control';
            castcontainer.appendChild(input);
        });
    servicebtn.addEventListener("click", () => {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'service[]';
            input.className = 'form-control';
            servicecontainer.appendChild(input);
        });

    function showMovieForm(formName) {
        const showApiForm = formName === "api";
        apiMovieForm.hidden = !showApiForm;
        manualMovieForm.hidden = showApiForm;

        MovieFormButtons.forEach((button) => {
            const isSelected = button.dataset.movieForm === formName;
            button.classList.toggle("btn-primary", isSelected);
            button.classList.toggle("btn-outline-primary", !isSelected);
            button.setAttribute("aria-pressed", String(isSelected));
        });
    }

    MovieFormButtons.forEach((button) => {
        button.addEventListener("click", () => {
            showMovieForm(button.dataset.movieForm);
        });
    });

    showMovieForm("<?php echo htmlspecialchars($active_form); ?>");
</script>
    </main>
    <?php render_flash_messages(); ?>
    <?php render_scripts(); ?>
</body>
</html>