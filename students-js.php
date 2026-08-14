<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript Student CRUD</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <a href="index.php" class="nav-link">Browse Rooms</a>
                <a href="rooms.php" class="nav-link">Rooms CRUD</a>
                <a href="students-js.php" class="nav-link active">JS Student CRUD</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="page-header mb-4">
            <div>
                <h1 class="page-title">JavaScript Student CRUD</h1>
                <p class="page-subtitle">Create, read, update, delete, and search student records without reloading the page.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal">+ Add Student</button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Search Students</label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name or course...">
                    </div>
                    <div class="col-md-4">
                        <button type="button" id="refreshBtn" class="btn btn-secondary w-100">Refresh</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentTable"></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Student Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="studentForm">
                    <div class="modal-body">
                        <input type="hidden" id="studentId">

                        <div class="mb-3">
                            <label for="studentName" class="form-label">Name *</label>
                            <input type="text" id="studentName" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="studentCourse" class="form-label">Course *</label>
                            <input type="text" id="studentCourse" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveStudentBtn">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/students.js"></script>
</body>
</html>
