import './bootstrap';

const API = '/api/v1/tasks';
let currentPage = 1;
let lastPage = 1;

const $ = (id) => document.getElementById(id);
const token = () => $('token').value.trim();

function headers() {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token()}`,
    };
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: { ...headers(), ...(options.headers || {}) },
    });

    if (response.status === 204) return null;

    const result = await response.json().catch(() => ({
        message: 'Server mengembalikan respons yang tidak valid.',
    }));

    if (!response.ok) throw result;
    return result;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[char]);
}

function showError(error) {
    const errors = error?.errors || {};
    const details = Object.entries(errors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n');
    $('formError').textContent = details || error?.message || 'Terjadi kesalahan.';
}

function resetForm() {
    $('taskForm').reset();
    $('taskId').value = '';
    $('formHeading').textContent = 'Tambah tugas';
    $('saveBtn').textContent = 'Simpan tugas';
    $('cancelBtn').hidden = true;
    $('formError').textContent = '';
}

async function loadTasks(page = currentPage) {
    $('taskList').innerHTML = '<div class="empty">Memuat data...</div>';

    try {
        const query = new URLSearchParams({ page, per_page: 10 });
        const result = await apiRequest(`${API}?${query}`);

        currentPage = result.meta.current_page;
        lastPage = result.meta.last_page;
        $('pageInfo').textContent = `Halaman ${currentPage} dari ${lastPage}`;
        $('prevBtn').disabled = currentPage <= 1;
        $('nextBtn').disabled = currentPage >= lastPage;

        if (!result.data.length) {
            $('taskList').innerHTML = '<div class="empty">Belum ada tugas. Tambahkan tugas pertama kamu.</div>';
            return;
        }

        $('taskList').innerHTML = result.data.map((task) => `
            <article class="task-card">
                <div>
                    <h3>${escapeHtml(task.title)}</h3>
                    <p>${escapeHtml(task.description || 'Tidak ada deskripsi')}</p>
                    <div class="task-meta">
                        <span class="status ${escapeHtml(task.status)}">${escapeHtml(task.status_label || task.status)}</span>
                        <span>Tenggat: ${task.due_date ? escapeHtml(new Date(task.due_date).toLocaleDateString('id-ID')) : '-'}</span>
                    </div>
                </div>
                <div class="task-buttons">
                    <button type="button" data-action="edit" data-id="${escapeHtml(task.id)}">Edit</button>
                    <button type="button" class="delete" data-action="delete" data-id="${escapeHtml(task.id)}">Hapus</button>
                </div>
            </article>
        `).join('');
    } catch (error) {
        $('taskList').innerHTML = `<div class="empty">${escapeHtml(error.message || 'Gagal mengambil data. Periksa token API.')}</div>`;
        $('connection').textContent = 'Koneksi gagal';
        $('connection').classList.remove('ok');
    }
}

$('connectBtn').addEventListener('click', async () => {
    try {
        await loadTasks(1);
        $('connection').textContent = 'API berhasil dihubungkan';
        $('connection').classList.add('ok');
    } catch {
        $('connection').textContent = 'Koneksi gagal';
        $('connection').classList.remove('ok');
    }
});

$('taskForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    $('formError').textContent = '';

    const id = $('taskId').value;
    const dueDate = $('dueDate').value;
    const payload = {
        title: $('title').value.trim(),
        description: $('description').value.trim() || null,
        status: $('status').value,
        due_date: dueDate ? `${dueDate}T00:00:00` : null,
    };

    try {
        await apiRequest(id ? `${API}/${id}` : API, {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });
        resetForm();
        await loadTasks(1);
    } catch (error) {
        showError(error);
    }
});

$('taskList').addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const id = button.dataset.id;

    if (button.dataset.action === 'delete') {
        if (!confirm('Yakin ingin menghapus tugas ini?')) return;
        try {
            await apiRequest(`${API}/${id}`, { method: 'DELETE' });
            await loadTasks(currentPage);
        } catch (error) {
            alert(error.message || 'Gagal menghapus tugas.');
        }
    }

    if (button.dataset.action === 'edit') {
        try {
            const result = await apiRequest(`${API}/${id}`);
            const task = result.data;
            $('taskId').value = task.id;
            $('title').value = task.title;
            $('description').value = task.description || '';
            $('status').value = task.status;
            $('dueDate').value = task.due_date ? task.due_date.slice(0, 10) : '';
            $('formHeading').textContent = 'Edit tugas';
            $('saveBtn').textContent = 'Simpan perubahan';
            $('cancelBtn').hidden = false;
            $('formError').textContent = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (error) {
            alert(error.message || 'Gagal mengambil detail tugas.');
        }
    }
});

$('cancelBtn').addEventListener('click', resetForm);
$('refreshBtn').addEventListener('click', () => loadTasks(currentPage));
$('prevBtn').addEventListener('click', () => loadTasks(Math.max(1, currentPage - 1)));
$('nextBtn').addEventListener('click', () => loadTasks(Math.min(lastPage, currentPage + 1)));
