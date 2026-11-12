<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Task Management API</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container">
        <header class="hero">
            <div>
                <p class="eyebrow">LARAVEL 12 · REST API</p>
                <h1>Task Management</h1>
                <p class="subtitle">Kelola daftar tugas dengan API sederhana tanpa database.</p>
            </div>
            <span class="api-badge">API v1</span>
        </header>

        <section class="panel">
            <h2>Autentikasi API</h2>
            <div class="token-row">
                <input id="token" type="password" value="rahasia_tugas_api_2026" placeholder="Bearer token">
                <button id="connectBtn" class="button button-dark">Hubungkan</button>
            </div>
            <p class="hint">Token demo: rahasia_tugas_api_2026</p>
            <p id="connection" class="connection">Belum terhubung</p>
        </section>

        <section class="panel">
            <h2 id="formHeading">Tambah tugas</h2>
            <form id="taskForm">
                <input type="hidden" id="taskId">
                <label for="title">Nama tugas</label>
                <input id="title" maxlength="150" required placeholder="Contoh: Mengerjakan tugas Laravel">
                <label for="description">Deskripsi</label>
                <textarea id="description" maxlength="1000" rows="3" placeholder="Keterangan tugas (opsional)"></textarea>
                <div class="form-grid">
                    <div>
                        <label for="status">Status</label>
                        <select id="status">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label for="dueDate">Tanggal tenggat</label>
                        <input id="dueDate" type="date">
                    </div>
                </div>
                <div id="formError" class="error" role="alert"></div>
                <div class="actions">
                    <button class="button" type="submit" id="saveBtn">Simpan tugas</button>
                    <button class="button button-light" type="button" id="cancelBtn" hidden>Batal edit</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="list-heading">
                <div>
                    <h2>Daftar tugas</h2>
                    <p class="hint">Data disimpan di storage/app/tasks.json</p>
                </div>
                <button id="refreshBtn" class="button button-light">Muat ulang</button>
            </div>
            <div id="taskList" class="task-list">
                <div class="empty">Hubungkan API untuk menampilkan tugas.</div>
            </div>
            <div class="pagination">
                <button id="prevBtn" class="button button-light" disabled>Sebelumnya</button>
                <span id="pageInfo">Halaman 1</span>
                <button id="nextBtn" class="button button-light" disabled>Berikutnya</button>
            </div>
        </section>

        <footer>Task Management API · Laravel 12 · JSON storage</footer>
    </main>
</body>
</html>