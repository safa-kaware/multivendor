<?php

require_once "../config/app.php";


/*
|--------------------------------------------------------------------------
| Require Admin
|--------------------------------------------------------------------------
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| Fetch Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.status,
        c.created_at,

        COUNT(p.id) AS product_count

     FROM categories c

     LEFT JOIN products p
        ON p.category_id = c.id

     GROUP BY
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.status,
        c.created_at

     ORDER BY c.created_at DESC"
);


$categories = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Manage Categories | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div
    class="d-flex justify-content-between align-items-center mb-4"
>


    <div>

        <h1 class="fw-bold">

            Categories

        </h1>


        <p class="text-muted mb-0">

            Manage product categories.

        </p>

    </div>


    <a
        href="<?= BASE_URL ?>admin/category-add.php"
        class="btn btn-dark"
    >

        <i class="bi bi-plus-lg"></i>

        Add Category

    </a>


</div>


<div
    class="card border-0 shadow-sm"
>


    <div
        class="card-body"
    >


        <div
            class="table-responsive"
        >


            <table
                class="table table-hover align-middle"
            >


                <thead>

                    <tr>

                        <th>
                            Image
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Slug
                        </th>

                        <th>
                            Products
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        empty($categories)
                    ): ?>


                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5 text-muted"
                            >

                                No categories found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>


                            <tr>


                                <!-- Image -->


                                <td>


                                    <?php if (
                                        !empty(
                                            $category["image"]
                                        )
                                    ): ?>


                                        <img
                                            src="<?= BASE_URL ?>uploads/categories/<?= e(
                                                $category["image"]
                                            ) ?>"
                                            alt="<?= e(
                                                $category["name"]
                                            ) ?>"
                                            style="
                                                width: 60px;
                                                height: 60px;
                                                object-fit: cover;
                                            "
                                            class="rounded"
                                        >


                                    <?php else: ?>


                                        <div
                                            class="bg-light rounded d-flex align-items-center justify-content-center"
                                            style="
                                                width: 60px;
                                                height: 60px;
                                            "
                                        >

                                            <i
                                                class="bi bi-image text-muted"
                                            ></i>

                                        </div>


                                    <?php endif; ?>


                                </td>


                                <!-- Name -->


                                <td>

                                    <strong>

                                        <?= e(
                                            $category["name"]
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $category["description"]
                                        )
                                    ): ?>


                                        <br>


                                        <small
                                            class="text-muted"
                                        >

                                            <?= e(
                                                mb_strimwidth(
                                                    $category["description"],
                                                    0,
                                                    60,
                                                    "..."
                                                )
                                            ) ?>

                                        </small>


                                    <?php endif; ?>


                                </td>


                                <!-- Slug -->


                                <td>

                                    <code>

                                        <?= e(
                                            $category["slug"]
                                        ) ?>

                                    </code>

                                </td>


                                <!-- Product Count -->


                                <td>

                                    <span
                                        class="badge text-bg-secondary"
                                    >

                                        <?= e(
                                            $category["product_count"]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- Status -->


                                <td>


                                    <?php if (
                                        $category["status"]
                                        === "active"
                                    ): ?>


                                        <span
                                            class="badge text-bg-success"
                                        >

                                            Active

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="badge text-bg-secondary"
                                        >

                                            Inactive

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- Created -->


                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $category["created_at"]
                                        )
                                    ) ?>

                                </td>


                                <!-- Actions -->


                                <td>


                                    <div
                                        class="d-flex gap-2"
                                    >


                                        <a
                                            href="<?= BASE_URL ?>admin/category-edit.php?id=<?= e(
                                                $category["id"]
                                            ) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >

                                            <i
                                                class="bi bi-pencil"
                                            ></i>

                                        </a>


                                        <a
                                            href="<?= BASE_URL ?>admin/category-delete.php?id=<?= e(
                                                $category["id"]
                                            ) ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this category?');"
                                        >

                                            <i
                                                class="bi bi-trash"
                                            ></i>

                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


<?php

require_once "includes/footer.php";

?>