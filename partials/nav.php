<?php
// partials/nav.php
// Usage: call render_nav() immediately after the opening <body> tag.
$isLoggedIn = is_logged_in();
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container">
        <!-- TODO: Replace UCID with your UCID. -->
        <a class="navbar-brand" href="<?php echo project_url("index.php"); ?>">Krish's Project</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#projectNav" aria-controls="projectNav"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="projectNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo project_url("index.php"); ?>">Home</a>
                </li>
                <!--<li class="nav-item">
                    <a class="nav-link" href="<?php /*echo project_url("guides.php");*/ ?>">Guides</a>
                </li>-->
                <!-- Keep public project-entity links in this list. -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo project_url("movies.php"); ?>">Movies</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <?php // Add inside the existing if ($isLoggedIn) block, before Dashboard. ?>
                    <!--<li class="nav-item">
                        <a class="nav-link" href="<?php /*echo project_url("my_guides.php");*/ ?>">
                            My Saved Guides
                        </a>
                    </li>-->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo project_url("my_movies.php"); ?>">
                            My Saved Movies
                        </a>
                    </li>
                    <?php // Keep Dashboard, Profile, Admin links, and Logout below. ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo project_url("dashboard.php"); ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo project_url("profile.php"); ?>">Profile</a></li>
                    <?php if (has_role("Admin")): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">Manage Roles</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo project_url("admin/create_role.php"); ?>">Create Role</a></li>
                                <li><a class="dropdown-item" href="<?php echo project_url("admin/list_roles.php"); ?>">List Roles</a></li>
                                <li><a class="dropdown-item" href="<?php echo project_url("admin/assign_roles.php"); ?>">Assign Roles</a></li>
                            </ul>
                        </li>
                        <!--<li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">Manage Guides</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php /*echo project_url("admin/create_guide.php");*/ ?>">Create Guide</a></li>
                                <li><a class="dropdown-item" href="<?php /*echo project_url("admin/list_guides.php");*/ ?>">List Guides</a></li>
                                <?php // Inside the existing Manage Guides dropdown, after List Guides. ?>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php /*echo project_url("admin/guide_associations.php");*/ ?>">
                                        Guide Associations
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php /*echo project_url("admin/unassociated_guides.php");*/ ?>">
                                        Unassociated Guides
                                    </a>
                                </li>
                                <?php // Keep the closing dropdown elements below. ?>
                                <?php
                                // partials/nav.php
                                // Add only this item to the existing Manage Guides dropdown.
                                ?>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php /*echo project_url("admin/assign_guides.php");*/ ?>">
                                        Assign Guides
                                    </a>
                                </li>
                            </ul>
                        </li>-->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">Manage Movies</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo project_url("admin/create_movie.php"); ?>">Create Movie</a></li>
                                <li><a class="dropdown-item" href="<?php echo project_url("admin/list_movies.php"); ?>">List Movies</a></li>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php echo project_url("admin/movie_associations.php"); ?>">
                                        Movie Associations
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php echo project_url("admin/unassociated_movies.php"); ?>">
                                        Unassociated Movies
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="<?php echo project_url("admin/assign_movies.php"); ?>">
                                        Assign Movies
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">Manage Stock</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php /*echo project_url("admin/create_stock.php");*/ ?>">Create stock</a></li>
                                <li><a class="dropdown-item" href="<?php /*echo project_url("admin/list_stocks.php");*/ ?>">List Stocks</a></li>
                                <li><a class="dropdown-item" href="<?php /*echo project_url("admin/create_company.php");*/ ?>">Create Company</a></li>
                                <li><a class="dropdown-item" href="<?php /*echo project_url("admin/list_companies.php");*/ ?>">List Companies</a></li>
                            </ul>
                        </li> -->
                    <?php endif; ?>
                    <?php
                    // partials/nav.php
                    // Replace the existing Logout link inside the logged-in navigation block.
                    ?>
                    <li class="nav-item">
                        <form method="post"
                            action="<?php echo project_url("logout.php"); ?>"
                            class="d-inline">
                            <?php render_csrf_input(); ?>
                            <button type="submit" class="nav-link border-0 bg-transparent">
                                Logout
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo project_url("login.php"); ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo project_url("register.php"); ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<script src="/project/helpers.js"></script>