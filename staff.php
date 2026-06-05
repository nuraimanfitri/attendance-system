<?php
include "api/auth_check.php";

if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

if ($_SESSION['user']['role'] !== 'staff') {
  header("Location: index.php");
  exit;
}
?>

<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance System - Staff</title>

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">

  <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>

<script>
window.OneSignalDeferred = window.OneSignalDeferred || [];

OneSignalDeferred.push(async function(OneSignal) {

  await OneSignal.init({
    appId: "f0783ed7-9365-4ad2-bf61-b9b49c9aa053",
    serviceWorkerPath: "OneSignalSDKWorker.js"
  });

  await OneSignal.Notifications.requestPermission();

  await new Promise(resolve => setTimeout(resolve, 2000));

  const subId = OneSignal.User.PushSubscription.id;

  if (!subId) {
    console.log("No subscription ID yet.");
    return;
  }

  const res = await fetch("api/save_push_subscription.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      onesignal_subscription_id: subId
    })
  });

  const result = await res.json();

  if (result.ok && Notification.permission === "granted") {
    new Notification("Notification Subscribed", {
      body: "You will now receive attendance notifications.",
      icon: "https://imgur.com/m6aZbE4.png"
    });
  }

});
</script>
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
    <div id="staffDashCard" style="display:block;">
      <div class="dashWelcome">Welcome, <span class="dashName" id="who">—</span></div>

      <div class="dashGrid staffGrid">
        <div class="dashItem">
          <div class="dashLabel">ID</div>
          <div class="dashValue" id="uid">—</div>
        </div>
        <div class="dashItem">
          <div class="dashLabel">Position</div>
          <div class="dashValue" id="uposition">—</div>
        </div>
      </div>

      <div class="dashActions">
        <button id="mainClockBtn" class="btnClock" onclick="handleMainClockBtn()" style="background:#008000; color:#fff;">
          Clock In
        </button>
      </div>

      <div class="attBox">
        <div class="attRow">
          <span>Status</span>
          <b id="attStatus">—</b>
        </div>

        <div class="attRow">
          <span>Duration</span>
          <b id="attDur">—</b>
        </div>
      </div>

      <button id="staffLogBtn" class="dashBtn" onclick="toggleStaffLog()" style="background:#111827; color:#fff;">
        <i class="fa-regular fa-calendar-check"></i> View Log
      </button>

      <div id="staffLogBox" class="attBox hidden">
        <div class="sectionTitle">Attendance Log</div>

        <div class="formGrid" style="margin-bottom:12px;">
          <div class="inputGroup">
            <label for="staffLogDate">Date</label>
            <input id="staffLogDate" type="date">
          </div>

          <div class="inputGroup">
            <label>&nbsp;</label>
            <button class="dashBtn" onclick="loadStaffLog()" style="background:#111827; color:#fff;">
              Refresh Log
            </button>
          </div>
        </div>

        <div class="tableWrap">
          <table style="min-width:1200px;">
            <thead>
              <tr>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Status In</th>
                <th>Status Out</th>
                <th>Location In</th>
                <th>Location Out</th>
                <th>Photo In</th>
                <th>Photo Out</th>
                <th>Notes In</th>
                <th>Notes Out</th>
                <th>Duration</th>
              </tr>
            </thead>

            <tbody id="staffLogTbody">
              <tr><td colspan="11">Press View Log</td></tr>
            </tbody>
          </table>
        </div>

        <div class="msg" id="staffLogMsg"></div>
      </div>

      <button class="dashBtn" onclick="doLogout()" style="background:#dc2626; color:#fff;">
        <i class="fa fa-sign-out"></i> Logout
      </button>
    </div>
  </div>
</div>

<div class="modal" id="camModal">
  <div class="modalCard">
    <div class="modalTop">
      <b id="camTitle">Capture</b>
      <div style="display:flex; gap:8px; width:100%; justify-content:flex-end; flex-wrap:wrap;">
        <button class="btnClose" onclick="closeCamera()">X</button>
      </div>
    </div>

    <div
      id="cameraFrame"
style="
  width:100%;
  max-width:320px;
  aspect-ratio:1/1;
  position:relative;
  border-radius:14px;
  overflow:hidden;
  background:#000;
  margin:auto;
"
    >
      <video
        id="camVideo"
        autoplay
        playsinline
        muted
        style="
          position:absolute;
          inset:0;
          width:100%;
          height:100%;
          object-fit:cover;
          display:none;
        "
      ></video>

      <canvas
        id="camCanvas"
        style="
          position:absolute;
          inset:0;
          width:100%;
          height:100%;
          object-fit:cover;
          display:none;
        "
      ></canvas>

      <div
  id="cameraPlaceholder"
  class="cameraPlaceholder"
  style="
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#000;
    color:#fff;
    font-weight:600;
  "
>
  Camera Loading...
</div>
    </div>

    <div class="noteBox">
      <label for="clockNote">Notes</label>
      <textarea id="clockNote" placeholder="Write notes here"></textarea>
    </div>

    <div
  class="modalBtns"
  style="
    display:flex;
    align-items:center;
    justify-content:center;
    gap:20px;
    margin-top:14px;
  "
>

  <!-- RETAKE -->
  <button
    id="btnRetake"
    onclick="retakePhoto()"
    style="
      width:52px;
      height:52px;
      border:none;
      border-radius:50%;
      background:#111827;
      color:#fff;
      font-size:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      padding:0;
      line-height:1;
      flex-shrink:0;
    "
  >
    <i class="fa-solid fa-rotate-right"></i>
  </button>

  <!-- CAPTURE -->
  <button
    id="btnCapture"
    onclick="handleCameraFile()"
    style="
      width:72px;
      height:72px;
      border:none;
      border-radius:50%;
      background:#008000;
      color:#fff;
      font-size:24px;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      box-shadow:0 0 14px rgba(0,128,0,0.35);
      padding:0;
      line-height:1;
      flex-shrink:0;
    "
  >
    <i class="fa-solid fa-camera"></i>
  </button>

  <!-- CONFIRM -->
  <button
    id="btnConfirm"
    onclick="confirmClock()"
    style="
      width:52px;
      height:52px;
      border:none;
      border-radius:50%;
      background:#16a34a;
      color:#fff;
      font-size:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      padding:0;
      line-height:1;
      flex-shrink:0;
    "
  >
    <i class="fa-solid fa-check"></i>
  </button>

</div>
    <div class="msg" id="camMsg"></div>
  </div>
</div>

<script src="assets/js/staff.js"></script>
</body>
</html>