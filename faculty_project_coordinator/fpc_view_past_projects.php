<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch Logic
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$faculty_id = $_SESSION['faculty_id'];
$whereClause = "faculty_id = ?";
$params = [$faculty_id];
if (!empty($search)) {
    $whereClause .= " AND (topic LIKE ? OR student_name LIKE ? OR reg_no LIKE ? OR session LIKE ? OR supervisor_name LIKE ?)";
    $ps = "%$search%";
    array_push($params, $ps, $ps, $ps, $ps, $ps);
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM past_projects WHERE $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $conn->prepare("SELECT * FROM past_projects WHERE $whereClause ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX search
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'projects' => $projects,
        'pagination' => [
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $totalRecords
        ]
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Projects Repository - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #667eea; 
            --secondary: #764ba2; 
            --success: #10ac84;
            --info: #0984e3;
            --glass: rgba(255, 255, 255, 0.95); 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            color: #333;
        }
        .container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        
        /* Header Card */
        .header-card { 
            background: var(--glass); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            margin-bottom: 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 20px;
        }
        .header-card h1 { color: var(--primary); font-size: 28px; }
        .header-card p { color: #666; font-size: 15px; }

        .btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            text-decoration: none; 
            font-size: 14px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-outline { border: 2px solid var(--primary); color: var(--primary); background: transparent; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        /* Main Content */
        .main-card { 
            background: var(--glass); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            min-height: 400px;
        }

        .search-area {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .search-input-group {
            flex: 1;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e8f0;
            border-radius: 15px;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
        }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a4b0be;
            font-size: 18px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 15px;
            background: white;
        }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #fcfdff; }

        .project-topic { font-weight: 700; color: #2d3436; margin-bottom: 4px; display: block; }
        .student-info { font-size: 13px; color: #636e72; }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #eef2ff; color: var(--primary); }
        
        .pdf-link {
            color: #ff4757;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }
        .pdf-link:hover { text-decoration: underline; }

        /* Pagination */
        .pagination { 
            display: flex; 
            justify-content: center; 
            gap: 10px; 
            margin-top: 30px; 
        }
        .page-btn { 
            padding: 10px 18px; 
            background: white; 
            border-radius: 10px; 
            text-decoration: none; 
            color: var(--primary); 
            font-weight: 600;
            border: 1px solid #e1e8f0;
            transition: 0.3s;
        }
        .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-btn:hover:not(.active) { background: #f8f9fa; }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #636e72;
        }
        .no-data i { font-size: 48px; margin-bottom: 15px; opacity: 0.3; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <div>
                <h1><i class="fas fa-archive"></i> Past Projects Gallery</h1>
                <p>Browse and search through our historical project repository</p>
            </div>
            <div class="header-actions">
                <a href="fpc_upload_past_projects.php" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload New Projects
                </a>
                <a href="fpc_dashboard.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Search and List -->
        <div class="main-card">
            <div class="search-area">
                <div class="search-input-group">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by topic, student, reg no, or supervisor..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button onclick="handleSearch()" class="btn btn-primary" style="padding: 0 30px;">Search</button>
            </div>

            <div id="resultsContainer">
                <div class="table-responsive">
                    <table id="projectsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Topic & Student</th>
                                <th>Supervisor</th>
                                <th>Session</th>
                                <th>Resources</th>
                            </tr>
                        </thead>
                        <tbody id="projectsBody">
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="no-data">
                                            <i class="fas fa-search"></i>
                                            <p>No past projects found matching your criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $p): ?>
                                    <tr class="fade-in">
                                        <td>#<?php echo $p['id']; ?></td>
                                        <td>
                                            <span class="project-topic"><?php echo htmlspecialchars($p['topic']); ?></span>
                                            <div class="student-info">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($p['student_name'] ?: 'N/A'); ?> 
                                                &bull; <?php echo htmlspecialchars($p['reg_no']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-tie" style="color: #a4b0be; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($p['supervisor_name'] ?: 'N/A'); ?>
                                        </td>
                                        <td><span class="badge"><?php echo htmlspecialchars($p['session']); ?></span></td>
                                        <td>
                                            <?php if (!empty($p['pdf_path'])): ?>
                                                <a href="../<?php echo htmlspecialchars($p['pdf_path']); ?>" target="_blank" class="pdf-link">
                                                    <i class="fas fa-file-pdf"></i> View Report
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #a4b0be; font-size: 12px; font-style: italic;">No PDF available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="paginationContainer">
                    <?php if ($totalPages > 1): ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"
                               onclick="goToPage(event, <?php echo $i; ?>)">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const searchInput = document.getElementById('searchInput');
        let currentSearch = "<?php echo addslashes($search); ?>";
        let currentPage = <?php echo $page; ?>;

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });

        async function handleSearch() {
            currentSearch = searchInput.value;
            currentPage = 1;
            await updateList();
        }

        async function goToPage(e, page) {
            e.preventDefault();
            currentPage = page;
            await updateList();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function updateList() {
            const body = document.getElementById('projectsBody');
            const pag = document.getElementById('paginationContainer');
            
            // Show loading state if needed
            body.style.opacity = '0.5';

            try {
                const url = `?ajax=1&page=${currentPage}&search=${encodeURIComponent(currentSearch)}`;
                const response = await fetch(url);
                const data = await response.json();

                renderTable(data.projects);
                renderPagination(data.pagination);
                
                // Update URL without reload
                const newUrl = window.location.pathname + `?page=${currentPage}&search=${encodeURIComponent(currentSearch)}`;
                window.history.pushState({ path: newUrl }, '', newUrl);

            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                body.style.opacity = '1';
            }
        }

        function renderTable(projects) {
            const body = document.getElementById('projectsBody');
            if (!projects || projects.length === 0) {
                body.innerHTML = `<tr><td colspan="5"><div class="no-data"><i class="fas fa-search"></i><p>No past projects found matching your criteria.</p></div></td></tr>`;
                return;
            }

            body.innerHTML = projects.map(p => `
                <tr class="fade-in">
                    <td>#${p.id}</td>
                    <td>
                        <span class="project-topic">${escapeHtml(p.topic)}</span>
                        <div class="student-info">
                            <i class="fas fa-user"></i> ${escapeHtml(p.student_name || 'N/A')} 
                            &bull; ${escapeHtml(p.reg_no)}
                        </div>
                    </td>
                    <td>
                        <i class="fas fa-user-tie" style="color: #a4b0be; margin-right: 5px;"></i>
                        ${escapeHtml(p.supervisor_name || 'N/A')}
                    </td>
                    <td><span class="badge">${escapeHtml(p.session)}</span></td>
                    <td>
                        ${p.pdf_path ? `
                            <a href="../${escapeHtml(p.pdf_path)}" target="_blank" class="pdf-link">
                                <i class="fas fa-file-pdf"></i> View Report
                            </a>
                        ` : `<span style="color: #a4b0be; font-size: 12px; font-style: italic;">No PDF available</span>`}
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const container = document.getElementById('paginationContainer');
            if (data.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';
            for (let i = 1; i <= data.total_pages; i++) {
                html += `
                    <a href="#" class="page-btn ${i === data.current_page ? 'active' : ''}" 
                       onclick="goToPage(event, ${i})">
                        ${i}
                    </a>
                `;
            }
            container.innerHTML = html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

