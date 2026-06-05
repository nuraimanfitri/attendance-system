document.addEventListener("DOMContentLoaded", function(){
  setTodayInputs();
  loadAdminSession();
  updateAdminStats();
});

async function loadAdminSession(){
  try{
    const res = await fetch("api/session.php");
    const data = await res.json();

    if(!data.ok){
      window.location.href = "index.php";
      return;
    }

    const u = data.user;

    if(u.role !== "admin"){
      window.location.href = "index.php";
      return;
    }

    document.getElementById("adminName").textContent = u.name || "—";
    document.getElementById("adminNameTop").textContent = u.name || "—";
    document.getElementById("adminRole").textContent = u.role || "admin";
    document.getElementById("adminId").textContent = u.staff_id || "—";
    document.getElementById("adminPosition").textContent = u.position || "—";

  }catch(e){
    window.location.href = "index.php";
  }
}

function setTodayInputs(){
  const today = new Date().toISOString().slice(0,10);

  const attendanceDate = document.getElementById("attendanceDate");
  const absentDate = document.getElementById("absentDate");

  if(attendanceDate) attendanceDate.value = today;
  if(absentDate) absentDate.value = today;
}

function showAdminSection(sectionId, btn){
  document.querySelectorAll(".adminSection").forEach(sec => {
    sec.classList.add("hidden");
  });

  const section = document.getElementById(sectionId);
  if(section) section.classList.remove("hidden");

  document.querySelectorAll(".adminMenuBtn").forEach(b => {
    b.classList.remove("active");
  });

  if(btn) btn.classList.add("active");
}

async function updateAdminStats(){
  try{
    const res = await fetch("api/admin_stats.php");
    const data = await res.json();

    if(!data.ok) return;

    document.getElementById("statTotalUsers").textContent = data.totalUsers;
    document.getElementById("statActiveUsers").textContent = data.activeUsers;
    document.getElementById("statAttendanceToday").textContent = data.attendanceToday;
    document.getElementById("statAbsentToday").textContent = data.absentToday;

  }catch(e){
    console.log(e);
  }
}

async function loadUsers(){
  const tbody = document.getElementById("usersTbody");
  const msg = document.getElementById("usersMsg");

  tbody.innerHTML = `<tr><td colspan="6">Loading...</td></tr>`;

  try{
    const res = await fetch("api/users.php?t=" + new Date().getTime(), {
      cache: "no-store"
    });

    const data = await res.json();

    console.log("USERS API:", data);

    if(!data.ok){
      tbody.innerHTML = `<tr><td colspan="6">Failed load users</td></tr>`;
      return;
    }

    if(data.data.length === 0){
      tbody.innerHTML = `<tr><td colspan="6">No data</td></tr>`;
      return;
    }

    tbody.innerHTML = data.data.map(u => `
      <tr>
        <td>${escapeHtml(u.staff_id)}</td>
        <td>${escapeHtml(u.name)}</td>
        <td>${escapeHtml(u.password || "")}</td>
        <td>${escapeHtml(u.role)}</td>
        <td>${escapeHtml(u.position || "—")}</td>
        <td>${escapeHtml(u.status || "—")}</td>
        <td>${escapeHtml(u.allow_ot || "NO")}</td>
      </tr>
    `).join("");

    if(msg) msg.textContent = "";

  }catch(e){
    console.log(e);
    tbody.innerHTML = `<tr><td colspan="6">Server error</td></tr>`;
  }
}

async function loadAttendance(){
  const tbody = document.getElementById("attendanceTbody");
  const msg = document.getElementById("attendanceMsg");
  const date = document.getElementById("attendanceDate").value;

  tbody.innerHTML = `<tr><td colspan="11">Loading...</td></tr>`;

  try{
    const res = await fetch("api/attendance_list.php?date=" + encodeURIComponent(date));
    const data = await res.json();

    if(!data.ok){
      tbody.innerHTML = `<tr><td colspan="11">${escapeHtml(data.msg || "Failed")}</td></tr>`;
      return;
    }

    if(data.data.length === 0){
      tbody.innerHTML = `<tr><td colspan="11">No data</td></tr>`;
      return;
    }

    tbody.innerHTML = data.data.map(r => `
      <tr>
        <td>${escapeHtml(r.staff_id || "")}</td>
        <td>${escapeHtml(r.name || "")}</td>
        <td>${escapeHtml(r.position || "")}</td>
        <td>${escapeHtml(r.clock_in || "—")}</td>
        <td>${escapeHtml(r.status_in || "—")}</td>
        <td>${escapeHtml(r.clock_out || "—")}</td>
        <td>${escapeHtml(r.status_out || "—")}</td>
        <td>${mapLink(r.location_in)}</td>
        <td>${mapLink(r.location_out)}</td>
        <td>${photoLink(r.photo_in, "Photo In")}</td>
        <td>${photoLink(r.photo_out, "Photo Out")}</td>
      </tr>
    `).join("");

    if(msg) msg.textContent = "";

  }catch(e){
    tbody.innerHTML = `<tr><td colspan="11">Server error</td></tr>`;
  }
}

async function loadAbsentUsers(){
  const tbody = document.getElementById("absentTbody");
  const msg = document.getElementById("absentMsg");
  const date = document.getElementById("absentDate").value;

  tbody.innerHTML = `<tr><td colspan="3">Loading...</td></tr>`;

  try{
    const res = await fetch("api/absent.php?date=" + encodeURIComponent(date));
    const data = await res.json();

    if(!data.ok){
      tbody.innerHTML = `<tr><td colspan="3">${escapeHtml(data.msg || "Failed")}</td></tr>`;
      return;
    }

    if(data.data.length === 0){
      tbody.innerHTML = `<tr><td colspan="3">No absent staff</td></tr>`;
      return;
    }

    tbody.innerHTML = data.data.map(u => `
      <tr>
        <td>${escapeHtml(u.staff_id || "")}</td>
        <td>${escapeHtml(u.name || "")}</td>
        <td>${escapeHtml(u.position || "")}</td>
      </tr>
    `).join("");

    if(msg) msg.textContent = "";

  }catch(e){
    tbody.innerHTML = `<tr><td colspan="3">Server error</td></tr>`;
  }
}

async function loadAnalysisUsers(){
  const select = document.getElementById("analysisUserSelect");

  try{
    const res = await fetch("api/users.php");
    const data = await res.json();

    if(!data.ok) return;

    let html = `<option value="">-- Choose Staff --</option>`;

    data.data
      .filter(u => u.role === "staff")
      .forEach(u => {
        html += `<option value="${escapeHtml(u.staff_id)}">${escapeHtml(u.staff_id)} - ${escapeHtml(u.name)}</option>`;
      });

    select.innerHTML = html;

  }catch(e){
    select.innerHTML = `<option value="">Failed load</option>`;
  }
}

async function loadUserAnalysis(){
  const staffId = document.getElementById("analysisUserSelect").value;
  const tbody = document.getElementById("analysisTbody");

  if(!staffId){
    tbody.innerHTML = `<tr><td colspan="11">Choose staff first</td></tr>`;
    return;
  }

  tbody.innerHTML = `<tr><td colspan="11">Loading...</td></tr>`;

  try{
    const res = await fetch("api/user_analysis.php?staff_id=" + encodeURIComponent(staffId));
    const data = await res.json();

    if(!data.ok){
      tbody.innerHTML = `<tr><td colspan="11">${escapeHtml(data.msg || "Failed")}</td></tr>`;
      return;
    }

    if(data.data.length === 0){
      tbody.innerHTML = `<tr><td colspan="11">No data</td></tr>`;
      return;
    }

    let html = "";
let currentMonth = "";

data.data.forEach(r => {
  const d = new Date((r.clock_in || "").replace(" ", "T"));

  const month = isNaN(d)
    ? "Unknown Month"
    : d.toLocaleString("en-US", {
        month: "long",
        year: "numeric"
      });

  if (month !== currentMonth) {

  currentMonth = month;

  let totalMinutes = 0;

  data.data
    .filter(x => {
      const xd = new Date((x.clock_in || "").replace(" ", "T"));

      const xm = isNaN(xd)
        ? "Unknown Month"
        : xd.toLocaleString("en-US", {
            month: "long",
            year: "numeric"
          });

      return xm === month;
    })

    .forEach(x => {

      if(!x.clock_in || !x.clock_out) return;

      const start = new Date(x.clock_in.replace(" ", "T"));
      const end = new Date(x.clock_out.replace(" ", "T"));

      if(isNaN(start) || isNaN(end)) return;

      totalMinutes += Math.floor((end - start) / 60000);
    });

  const totalHours = Math.floor(totalMinutes / 60);
  const remainMinutes = totalMinutes % 60;

  html += `
  <tr>
    <td colspan="11"
      style="
        background:#111827;
        color:#fff;
        padding:12px 18px;
        font-size:12px;
        font-weight:900;
      ">
      ${month} &nbsp;&nbsp; | &nbsp;&nbsp; Total Duration: ${totalHours}h ${remainMinutes}m
    </td>
  </tr>
`;
}

  html += `
    <tr>
      <td>${escapeHtml(r.clock_in || "—")}</td>
      <td>${escapeHtml(r.clock_out || "—")}</td>
      <td>${mapLink(r.location_in)}</td>
      <td>${mapLink(r.location_out)}</td>
      <td>${photoLink(r.photo_in, "Photo In")}</td>
      <td>${photoLink(r.photo_out, "Photo Out")}</td>
      <td>${escapeHtml(r.notes_in || "—")}</td>
      <td>${escapeHtml(r.notes_out || "—")}</td>
      <td>${escapeHtml(r.status_in || "—")}</td>
      <td>${escapeHtml(r.status_out || "—")}</td>
      <td>${calcDuration(r.clock_in, r.clock_out)}</td>
    </tr>
  `;
});

tbody.innerHTML = html;

    document.getElementById("printUserBtn").classList.remove("hidden");

  }catch(e){
    tbody.innerHTML = `<tr><td colspan="11">Server error</td></tr>`;
  }
}

function mapLink(loc){
  if(!loc) return "—";

  const safe = escapeHtml(loc);
  const url = "https://www.google.com/maps?q=" + encodeURIComponent(loc);

  return `<a href="${url}" target="_blank">${safe}</a>`;
}

function photoLink(path, text){
  if(!path) return "—";

  return `<a href="${escapeAttr(path)}" target="_blank">${escapeHtml(text)}</a>`;
}

function calcDuration(clockIn, clockOut){
  if(!clockIn || !clockOut) return "—";

  const start = new Date(clockIn.replace(" ", "T"));
  const end = new Date(clockOut.replace(" ", "T"));

  if(isNaN(start) || isNaN(end)) return "—";

  let diff = Math.floor((end - start) / 1000);
  if(diff < 0) return "—";

  const h = Math.floor(diff / 3600);
  diff %= 3600;
  const m = Math.floor(diff / 60);

  return `${h}h ${m}m`;
}

async function doLogout(){
  await fetch("api/logout.php");
  localStorage.removeItem("attendance_user");
  sessionStorage.removeItem("attendance_user");
  window.location.href = "index.php";
}

async function addUser(){
  const msg = document.getElementById("addUserMsg");

  const data = new FormData();
  data.append("staff_id", document.getElementById("newId").value.trim());
  data.append("name", document.getElementById("newName").value.trim());
  data.append("password", document.getElementById("newPassword").value.trim());
  data.append("position", document.getElementById("newPosition").value.trim());
  data.append("status", document.getElementById("newStatus").value);
  data.append("role", document.getElementById("newRole").value);
  data.append("allow_ot", document.getElementById("newAllowOt").value);

  msg.textContent = "Saving...";

  try{
    const res = await fetch("api/add_user.php", {
      method: "POST",
      body: data
    });

    const result = await res.json();

    msg.textContent = result.msg;

    if(result.ok){
      loadUsers();
    }

  }catch(e){
    msg.textContent = "Server error";
  }
}

let usersEditMode = false;

function handleUsersEdit(){
  const editBtn = document.getElementById("editUsersBtn");
  const cancelBtn = document.getElementById("cancelUsersBtn");

  if (!usersEditMode) {
    enableUsersEditMode();
    usersEditMode = true;

    editBtn.textContent = "Confirm Update";
    cancelBtn.classList.remove("hidden");
    return;
  }

  confirmUpdateUsers();
}

function enableUsersEditMode(){
  const rows = document.querySelectorAll("#usersTbody tr");

  rows.forEach(row => {
    const tds = row.querySelectorAll("td");
    if (tds.length < 7) return;

    const name = tds[1].textContent.trim();
    const password = tds[2].textContent.trim();
    const role = tds[3].textContent.trim();
    const position = tds[4].textContent.trim();
    const status = tds[5].textContent.trim();
    const allow_ot = tds[6].textContent.trim();

    tds[1].innerHTML = `<input value="${escapeAttr(name)}">`;
    tds[2].innerHTML = `<input value="${escapeAttr(password)}">`;

    tds[3].innerHTML = `
      <select>
        <option value="admin" ${role === "admin" ? "selected" : ""}>admin</option>
        <option value="staff" ${role === "staff" ? "selected" : ""}>staff</option>
      </select>
    `;

    tds[4].innerHTML = `<input value="${escapeAttr(position === "—" ? "" : position)}">`;

    tds[5].innerHTML = `
      <select>
        <option value="active" ${status === "active" ? "selected" : ""}>active</option>
        <option value="inactive" ${status === "inactive" ? "selected" : ""}>inactive</option>
      </select>
    `;

    tds[6].innerHTML = `
      <select>
        <option value="NO" ${allow_ot === "NO" ? "selected" : ""}>NO</option>
        <option value="YES" ${allow_ot === "YES" ? "selected" : ""}>YES</option>
      </select>
    `;
  });
}

async function confirmUpdateUsers(){
  const rows = document.querySelectorAll("#usersTbody tr");
  const payload = [];

  rows.forEach(row => {
    const tds = row.querySelectorAll("td");
    if (tds.length < 7) return;

    const nameInput = tds[1].querySelector("input");
    const passwordInput = tds[2].querySelector("input");
    const roleSelect = tds[3].querySelector("select");
    const positionInput = tds[4].querySelector("input");
    const statusSelect = tds[5].querySelector("select");
    const allowOtSelect = tds[6].querySelector("select");

    if (!nameInput || !passwordInput || !roleSelect || !positionInput || !statusSelect || !allowOtSelect) return;

    payload.push({
      staff_id: tds[0].textContent.trim(),
      name: nameInput.value.trim(),
      password: passwordInput.value.trim(),
      role: roleSelect.value,
      position: positionInput.value.trim(),
      status: statusSelect.value,
      allow_ot: allowOtSelect.value
    });
  });

  if (payload.length === 0) {
    alert("No editable data found.");
    return;
  }

  try {
    const res = await fetch("api/update_users.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (!data.ok) {
      alert(data.msg || "Update failed");
      return;
    }

    alert(data.msg || "Updated");

    usersEditMode = false;
    document.getElementById("editUsersBtn").textContent = "Edit Users";

    const cancelBtn = document.getElementById("cancelUsersBtn");
    if (cancelBtn) cancelBtn.classList.add("hidden");

    loadUsers();
    updateAdminStats();

  } catch (e) {
    alert("Server error");
  }
}

function printUserAnalysis(){

  const staff =
    document.getElementById("analysisUserSelect")
    .selectedOptions[0].text;

  const table =
    document.querySelector("#analysisUsersSection .tableWrap")
    .innerHTML;

  const win = window.open("", "_blank");

  win.document.write(`
    <html>
    <head>
      <title>User Report</title>

      <style>
        @page{
  size: A4 landscape;
  margin: 8mm;
}

body{
  font-family: Arial, sans-serif;
  padding:0;
  margin:0;
}

h2{
  margin:0 0 10px;
  font-size:16px;
}

table{
  width:98%;
  border-collapse:collapse;
  table-layout:auto;
  font-size:8px;
}

th, td{
  border:1px solid #000 !important;
  padding:4px;
  text-align:left;
  vertical-align:top;
  word-break:break-word;
  overflow-wrap:anywhere;
}

th{
  background:#f3f4f6;
}

a{
  color:#000;
  text-decoration:none;
}

img{
  max-width:60px;
}
      </style>
    </head>

    <body>

      <h2>
        Attendance Report - ${staff}
      </h2>

      ${table}

    </body>
    </html>
  `);

  win.document.close();
  win.print();
}

function escapeHtml(str){
  return String(str ?? "")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

function escapeAttr(str){
  return escapeHtml(str);
}

function cancelUsersEdit(){
  usersEditMode = false;

  document.getElementById("editUsersBtn").textContent = "Edit Users";
  document.getElementById("cancelUsersBtn").classList.add("hidden");

  loadUsers();
}

function resetUserAnalysis(){
  document.getElementById("analysisUserSelect").value = "";
  document.getElementById("analysisTbody").innerHTML =
    `<tr><td colspan="11">Choose staff first</td></tr>`;

  const printBtn = document.getElementById("printUserBtn");
  if (printBtn) printBtn.classList.add("hidden");
}