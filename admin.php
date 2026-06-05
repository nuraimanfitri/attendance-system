<?php
include "api/auth_check.php";
if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}
?>

<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance System - Admin</title>

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="card">

  <div class="top-header">
    <img src="https://imgur.com/m6aZbE4.png" alt="Logo" onerror="this.src='https://placehold.co/120x40?text=MATHSPOWER'">
    <h1>
      <span>ATTENDANCE</span>
      <span>SYSTEM</span>
    </h1>
  </div>

  <div id="adminDashCard" class="adminShell">

    <aside class="adminSidebar">

      <div class="adminProfileCard">
        <div class="adminAvatar">
          <i class="fa fa-screwdriver-wrench"></i>
        </div>

        <div class="adminProfileText">
          <div class="adminProfileName" id="adminName">—</div>
          <div class="adminProfileRole" id="adminRole">admin</div>
        </div>
      </div>

      <div class="adminMenu">
        <button class="adminMenuBtn active" onclick="showAdminSection('dashboardHome', this)">
          <i class="fa fa-dashboard"></i> Dashboard
        </button>

        <button class="adminMenuBtn" onclick="showAdminSection('addUserSection', this)">
          <i class="fa fa-user-plus"></i> Add User
        </button>

        <button class="adminMenuBtn" onclick="loadUsers(); showAdminSection('usersSection', this)">
          <i class="fa fa-users"></i> View Users
        </button>

        <button class="adminMenuBtn" onclick="resetUserAnalysis(); loadAnalysisUsers(); showAdminSection('analysisUsersSection', this)">
          <i class="fa-solid fa-chart-line"></i> User Data
        </button>

        <button class="adminMenuBtn" onclick="loadAttendance(); showAdminSection('attendanceSection', this)">
          <i class="fa-regular fa-calendar-check"></i> Attendance
        </button>

        <button class="adminMenuBtn" onclick="loadAbsentUsers(); showAdminSection('absentSection', this)">
          <i class="fa-solid fa-user-xmark"></i> Absent
        </button>
      </div>

      <button class="adminLogoutBtn" onclick="doLogout()">
        <i class="fa fa-sign-out"></i> Logout
      </button>

    </aside>

    <main class="adminMain">

      <div class="adminTopbar">
        <div>
          <div class="adminTopTitle">Dashboard</div>
          <div class="adminTopSubtitle">
            Welcome back, <span id="adminNameTop">—</span>
          </div>
        </div>

        <div class="adminQuickInfo">
          <div class="miniChip">ID: <span id="adminId">—</span></div>
          <div class="miniChip">Position: <span id="adminPosition">—</span></div>
        </div>
      </div>

      <section id="dashboardHome" class="adminSection">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
          <div class="sectionTitle" style="margin:0;">
            Dashboard Overview
          </div>

          <button class="adminPrimaryBtn" style="min-width:unset;" onclick="updateAdminStats()">
            <i class="fa-solid fa-rotate-right"></i>
            Refresh Dashboard
          </button>
        </div>

        <div class="statsGrid">

          <div class="statCard statBlue">
            <div class="statLabel">Total Users</div>
            <div class="statValue" id="statTotalUsers">0</div>
          </div>

          <div class="statCard statGreen">
            <div class="statLabel">Active Users</div>
            <div class="statValue" id="statActiveUsers">0</div>
          </div>

          <div class="statCard statPurple">
            <div class="statLabel">Attendance Today</div>
            <div class="statValue" id="statAttendanceToday">0</div>
          </div>

          <div class="statCard statRed">
            <div class="statLabel">Absent Today</div>
            <div class="statValue" id="statAbsentToday">0</div>
          </div>

        </div>

      </section>

      <div id="addUserSection" class="sectionCard adminSection hidden">
        <div class="sectionTitle">Add User</div>

        <div class="formGrid">

          <div class="inputGroup">
            <label for="newId">ID</label>
            <input id="newId" placeholder="Contoh: MP001">
          </div>

          <div class="inputGroup">
            <label for="newName">Name</label>
            <input id="newName" placeholder="Nama staff">
          </div>

          <div class="inputGroup">
            <label for="newPassword">Password</label>
            <input id="newPassword" placeholder="Password">
          </div>

          <div class="inputGroup">
            <label for="newPosition">Position</label>
            <input id="newPosition" placeholder="Contoh: Teacher">
          </div>

          <div class="inputGroup">
            <label for="newRole">Role</label>
            <select id="newRole">
              <option value="staff">staff</option>
              <option value="admin">admin</option>
            </select>
          </div>

          <div class="inputGroup">
            <label for="newStatus">Status</label>
            <select id="newStatus">
              <option value="active">active</option>
              <option value="inactive">inactive</option>
            </select>
          </div>

          <div class="inputGroup">
            <label for="newAllowOt">Allow OT</label>
            <select id="newAllowOt">
              <option value="NO">NO</option>
              <option value="YES">YES</option>
            </select>
          </div>

          <div class="fullWidth">
            <button class="adminPrimaryBtn" onclick="addUser()">Save User</button>
          </div>

        </div>

        <div class="msg" id="addUserMsg"></div>
      </div>

      <div id="usersSection" class="sectionCard adminSection hidden">

        <div class="sectionTitle">User List</div>

        <div class="tableWrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Password</th>
                <th>Role</th>
                <th>Position</th>
                <th>Status</th>
                <th>Allow OT</th>
              </tr>
            </thead>

            <tbody id="usersTbody">
              <tr>
                <td colspan="7">No data</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="msg" id="usersMsg"></div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
          <button id="editUsersBtn" class="adminPrimaryBtn" onclick="handleUsersEdit()">
            Edit Users
          </button>

          <button id="cancelUsersBtn" class="adminPrimaryBtn hidden" onclick="cancelUsersEdit()">
            Cancel
          </button>
        </div>

      </div>

      <div id="attendanceSection" class="sectionCard adminSection hidden">

        <div class="sectionTitle">Attendance List</div>

        <div class="formGrid" style="margin-bottom:12px;">
          <div class="inputGroup">
            <label for="attendanceDate">Date</label>
            <input id="attendanceDate" type="date">
          </div>

          <div class="inputGroup">
            <label>&nbsp;</label>
            <button class="adminPrimaryBtn" onclick="loadAttendance()">
              Refresh Attendance
            </button>
          </div>
        </div>

        <div class="tableWrap">
          <table>
            <thead>
              <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Position</th>
                <th>Clock In</th>
                <th>Status In</th>
                <th>Clock Out</th>
                <th>Status Out</th>
                <th>Location In</th>
                <th>Location Out</th>
                <th>Photo In</th>
                <th>Photo Out</th>
              </tr>
            </thead>

            <tbody id="attendanceTbody">
              <tr>
                <td colspan="11">No data</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="msg" id="attendanceMsg"></div>

      </div>

      <div id="absentSection" class="sectionCard adminSection hidden">

        <div class="sectionTitle">Absent Staff</div>

        <div class="formGrid" style="margin-bottom:12px;">
          <div class="inputGroup">
            <label for="absentDate">Date</label>
            <input id="absentDate" type="date">
          </div>

          <div class="inputGroup">
            <label>&nbsp;</label>
            <button class="adminPrimaryBtn" onclick="loadAbsentUsers()">
              Refresh Absent
            </button>
          </div>
        </div>

        <div class="tableWrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Position</th>
              </tr>
            </thead>

            <tbody id="absentTbody">
              <tr>
                <td colspan="3">No data</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="msg" id="absentMsg"></div>

      </div>

      <div id="analysisUsersSection" class="sectionCard adminSection hidden">

        <div class="sectionTitle">User Data</div>

        <div class="formGrid" style="margin-bottom:12px;">
          <div class="inputGroup">
            <label for="analysisUserSelect">Choose Staff</label>
            <select id="analysisUserSelect">
              <option value="">-- Choose Staff --</option>
            </select>
          </div>

          <div class="inputGroup">
            <label>&nbsp;</label>
            <button class="adminPrimaryBtn" onclick="loadUserAnalysis()">
  Show Data
</button>

<button
  id="printUserBtn"
  class="adminPrimaryBtn hidden"
  onclick="printUserAnalysis()"
  style="background:#16a34a;"
>
  <i class="fa fa-print"></i> Print PDF
</button>
          </div>
        </div>

        <div class="tableWrap">
          <table style="min-width:1400px;">
            <thead>
              <tr>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Location In</th>
                <th>Location Out</th>
                <th>Photo In</th>
                <th>Photo Out</th>
                <th>Notes In</th>
                <th>Notes Out</th>
                <th>Status In</th>
                <th>Status Out</th>
                <th>Duration</th>
              </tr>
            </thead>

            <tbody id="analysisTbody">
              <tr>
                <td colspan="11">Choose staff first</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="msg" id="analysisMsg"></div>

      </div>

    </main>

  </div>
</div>

<script src="assets/js/admin.js"></script>

</body>
</html>