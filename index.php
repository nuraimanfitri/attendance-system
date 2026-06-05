<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance System - Login</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bungee&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

  <div class="main-content">
    <div id="loginCard">
      <div class="loginTitle">
        <i class="fa-solid fa-user-lock"></i>
        <h2>User Login</h2>
      </div>

      <label><i class="fa-solid fa-user"></i> ID</label>
      <select id="userId">
        <option value="">-- Choose ID --</option>
      </select>

      <label><i class="fa-solid fa-lock"></i> Password</label>
      <input id="password" type="password" placeholder="Password">

      <div class="rememberRow">
        <input type="checkbox" id="rememberMe">
        <label for="rememberMe">Stay Logged In</label>
      </div>

      <button id="loginBtn" onclick="login()" style="background:#008000; color:#fff;">
        <i class="fa fa-sign-in"></i> Log in
      </button>

      <div class="msg" id="msg"></div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/js/auth.js"></script>

<style>
.select2-container--default .select2-selection--single{
  height:52px !important;
  border:none !important;
  border-radius:14px !important;
  background:#f5f5f5 !important;
  padding:0 14px !important;
  display:flex !important;
  align-items:center !important;
  font-family:'Poppins', sans-serif !important;
  box-shadow:none !important;
}

.select2-container .select2-selection__rendered{
  line-height:52px !important;
  color:#333 !important;
  font-size:15px !important;
}

.select2-container .select2-selection__placeholder{
  color:#999 !important;
}

.select2-container .select2-selection__arrow{
  height:52px !important;
  right:10px !important;
}

.select2-dropdown{
  border:none !important;
  border-radius:14px !important;
  overflow:hidden !important;
  box-shadow:0 8px 18px rgba(0,0,0,.15) !important;
  font-family:'Poppins', sans-serif !important;
}

.select2-search__field{
  border:none !important;
  border-radius:10px !important;
  padding:10px !important;
  background:#f5f5f5 !important;
  outline:none !important;
}

.select2-results__option{
  padding:12px 14px !important;
  font-size:14px !important;
}

.select2-results__option--highlighted{
  background:#008000 !important;
}
</style>
</body>
</html>