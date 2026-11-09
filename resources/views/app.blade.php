<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aplikasi Tugas Kelas</title>
<style>
  body{font-family:system-ui,sans-serif;max-width:860px;margin:24px auto;padding:0 12px;color:#222}
  input,select,textarea,button{padding:8px;margin:4px 0;font:inherit}
  button{cursor:pointer}
  .card{border:1px solid #ddd;border-radius:8px;padding:12px;margin:10px 0}
  .error{color:#b00020;background:#fdecea;padding:8px;border-radius:6px;margin:8px 0}
  .ok{color:#1b5e20}
  .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .muted{color:#666;font-size:.9em}
  .hidden{display:none}
</style>
</head>
<body>
<h1>Panel Tugas Kelas</h1>
<div id="error" class="error hidden"></div>
<div id="loading" class="muted hidden">Memuat...</div>

<!-- LOGIN / REGISTER -->
<section id="auth">
  <div class="card">
    <h3>Login</h3>
    <input id="l-email" type="email" placeholder="Email">
    <input id="l-pass" type="password" placeholder="Password">
    <button onclick="login()">Masuk</button>
  </div>
  <div class="card">
    <h3>Registrasi</h3>
    <input id="r-name" placeholder="Nama">
    <input id="r-email" type="email" placeholder="Email">
    <input id="r-pass" type="password" placeholder="Password (min 8)">
    <input id="r-pass2" type="password" placeholder="Ulangi password">
    <select id="r-role" onchange="toggleClass()">
      <option value="student">Siswa</option>
      <option value="teacher">Guru</option>
    </select>
    <select id="r-class"></select>
    <button onclick="register()">Daftar</button>
  </div>
</section>

<!-- APLIKASI -->
<section id="app" class="hidden">
  <div class="row">
    <strong id="who"></strong>
    <button onclick="logout()">Logout</button>
  </div>

  <div class="card row">
    <input id="q" placeholder="Cari judul..." oninput="debounceLoad()">
    <select id="status" onchange="page=1;loadTasks()">
      <option value="">Semua status</option>
      <option value="pending">Belum selesai</option>
      <option value="done">Selesai</option>
    </select>
  </div>

  <div id="teacher-form" class="card hidden">
    <h3 id="form-title">Tambah Tugas</h3>
    <input id="t-title" placeholder="Judul">
    <textarea id="t-desc" placeholder="Deskripsi" rows="2"></textarea>
    <input id="t-due" type="datetime-local">
    <select id="t-class"></select>
    <button onclick="saveTask()">Simpan</button>
    <button onclick="resetForm()">Batal</button>
  </div>

  <div id="list"></div>
  <div class="row">
    <button onclick="go(-1)">&laquo; Sebelumnya</button>
    <span id="pageinfo" class="muted"></span>
    <button onclick="go(1)">Berikutnya &raquo;</button>
  </div>
</section>

<script>
const API = '/api';
let token = localStorage.getItem('token');
let me = null, page = 1, lastPage = 1, editingId = null, timer;

const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

function showError(msg){ $('error').textContent = msg; $('error').classList.remove('hidden'); }
function clearError(){ $('error').classList.add('hidden'); }

async function api(path, method='GET', body){
  clearError(); $('loading').classList.remove('hidden');
  try {
    const res = await fetch(API + path, {
      method,
      headers: { 'Accept':'application/json', 'Content-Type':'application/json',
                 ...(token ? {Authorization:'Bearer '+token} : {}) },
      body: body ? JSON.stringify(body) : undefined
    });
    if (res.status === 204) return null;
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (res.status === 401 && token) { logout(); }
      const detail = data.errors ? Object.values(data.errors).flat().join(' ') : '';
      throw new Error(detail || data.message || 'Terjadi kesalahan (' + res.status + ')');
    }
    return data;
  } catch (e) { showError(e.message); throw e; }
  finally { $('loading').classList.add('hidden'); }
}

async function loadClasses(){
  const {data} = await api('/classes');
  const opts = data.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
  $('r-class').innerHTML = opts; $('t-class').innerHTML = opts;
}
function toggleClass(){ $('r-class').classList.toggle('hidden', $('r-role').value === 'teacher'); }

async function login(){
  try {
    const {data} = await api('/login','POST',{email:$('l-email').value,password:$('l-pass').value});
    startSession(data);
  } catch(e){}
}
async function register(){
  const role = $('r-role').value;
  try {
    const {data} = await api('/register','POST',{
      name:$('r-name').value,email:$('r-email').value,password:$('r-pass').value,
      password_confirmation:$('r-pass2').value,role,
      class_id: role==='student' ? $('r-class').value : null
    });
    startSession(data);
  } catch(e){}
}
function startSession(data){
  token = data.token; me = data.user; localStorage.setItem('token', token); showApp();
}
async function logout(){
  try { if(token) await fetch(API+'/logout',{method:'POST',headers:{Accept:'application/json',Authorization:'Bearer '+token}}); } catch(e){}
  token = null; me = null; localStorage.removeItem('token');
  $('app').classList.add('hidden'); $('auth').classList.remove('hidden');
}
async function showApp(){
  if (!me) { try { me = (await api('/me')).data; } catch(e){ return; } }
  $('auth').classList.add('hidden'); $('app').classList.remove('hidden');
  $('who').textContent = me.name + ' (' + (me.role==='teacher'?'Guru':'Siswa') + ')';
  $('teacher-form').classList.toggle('hidden', me.role !== 'teacher');
  page = 1; loadTasks();
}

function debounceLoad(){ clearTimeout(timer); timer = setTimeout(()=>{page=1;loadTasks();}, 300); }
function go(d){ const n = page + d; if(n>=1 && n<=lastPage){ page=n; loadTasks(); } }

async function loadTasks(){
  const p = new URLSearchParams({page});
  if ($('q').value) p.set('search', $('q').value);
  if ($('status').value && me.role==='student') p.set('status', $('status').value);
  try {
    const res = await api('/tasks?'+p);
    lastPage = res.last_page;
    $('pageinfo').textContent = `Halaman ${res.current_page} dari ${res.last_page} (${res.total} tugas)`;
    $('list').innerHTML = res.data.length ? res.data.map(renderTask).join('') : '<p class="muted">Belum ada tugas.</p>';
  } catch(e){}
}

function renderTask(t){
  const due = new Date(t.due_date).toLocaleString('id-ID');
  let actions = '';
  if (me.role === 'student') {
    actions = t.is_done ? '<span class="ok">✓ Selesai</span>'
      : `<button onclick="complete(${t.id})">Tandai selesai</button>`;
  } else {
    actions = `<button onclick="editTask(${t.id})">Ubah</button> <button onclick="delTask(${t.id})">Hapus</button>`;
  }
  return `<div class="card"><strong>${esc(t.title)}</strong>
    <div class="muted">${esc(t.classroom?.name ?? '')} · Tenggat: ${esc(due)}</div>
    <p>${esc(t.description)}</p>${actions}</div>`;
}

async function complete(id){ try{ await api('/tasks/'+id+'/complete','POST'); loadTasks(); }catch(e){} }

async function saveTask(){
  const body = {title:$('t-title').value, description:$('t-desc').value,
                due_date:$('t-due').value, class_id:$('t-class').value};
  try {
    editingId ? await api('/tasks/'+editingId,'PUT',body) : await api('/tasks','POST',body);
    resetForm(); loadTasks();
  } catch(e){}
}
async function editTask(id){
  const res = await api('/tasks?per_page=50'); // sederhana: ambil dari daftar
  const t = res.data.find(x => x.id === id); if(!t) return;
  editingId = id; $('form-title').textContent = 'Ubah Tugas';
  $('t-title').value = t.title; $('t-desc').value = t.description ?? '';
  $('t-due').value = t.due_date.slice(0,16); $('t-class').value = t.class_id;
  window.scrollTo({top:0,behavior:'smooth'});
}
async function delTask(id){
  if(!confirm('Hapus tugas ini?')) return;
  try{ await api('/tasks/'+id,'DELETE'); loadTasks(); }catch(e){}
}
function resetForm(){
  editingId = null; $('form-title').textContent = 'Tambah Tugas';
  ['t-title','t-desc','t-due'].forEach(i => $(i).value = '');
}

loadClasses().then(toggleClass);
if (token) showApp();
</script>
</body>
</html>