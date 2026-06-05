let pendingType = null;
let capturedDataUrl = "";
let currentClockType = "in";
let cameraStream = null;
let pendingLocationText = "";

document.addEventListener("DOMContentLoaded", function () {
  loadStaffStatus();
});

async function loadStaffStatus() {
  try {
    const res = await fetch("api/status.php");
    const data = await res.json();

    if (!data.ok) {
      window.location.href = "index.php";
      return;
    }

    renderStatus(data);
  } catch (e) {
    console.log(e);
  }
}

function renderStatus(res) {
  const u = res.user || {};
  const d = res.data || null;

  document.getElementById("who").textContent = u.name || "—";
  document.getElementById("uid").textContent = u.staff_id || "—";
  document.getElementById("uposition").textContent =
    (u.position || "—").toUpperCase();

  const statusEl = document.getElementById("attStatus");

  if (!d) {
    statusEl.textContent = "NO RECORD";
    document.getElementById("attDur").textContent = "—";

    setStatusColor(statusEl, "");
    setMainClockButton("in");
    return;
  }

  if (res.open) {
    statusEl.textContent =
      "Clock In at " + (d.clock_in || "—") +
      " (" + (d.status_in || "—") + ")";

    setStatusColor(statusEl, d.status_in);
    startLiveDuration(d.clock_in);
    setMainClockButton("out");
  } else {
    statusEl.textContent =
      "Clock Out at " + (d.clock_out || "—") +
      " (" + (d.status_out || d.status_in || "—") + ")";

    setStatusColor(statusEl, d.status_out || d.status_in);

    clearInterval(durationTimer);
    document.getElementById("attDur").textContent =
      d.clock_in && d.clock_out ? calcDuration(d.clock_in, d.clock_out) : "—";

    setMainClockButton("in");
  }
}

function setLocationLink(id, loc) {
  const el = document.getElementById(id);

  if (!loc) {
    el.textContent = "—";
    el.removeAttribute("href");
    return;
  }

  el.textContent = loc;
  el.href = "https://www.google.com/maps?q=" + encodeURIComponent(loc);
  el.target = "_blank";
}

function setPhotoLink(id, path, text) {
  const el = document.getElementById(id);

  if (!path) {
    el.textContent = "—";
    el.removeAttribute("href");
    return;
  }

  el.textContent = text;
  el.href = path;
  el.target = "_blank";
}

function handleMainClockBtn() {
  startClockFlow(currentClockType);
}

function setMainClockButton(type) {
  const btn = document.getElementById("mainClockBtn");

  if (!btn) return;

  currentClockType = type;

  if (type === "out") {
    btn.innerHTML = `
      <i class="fa-solid fa-right-from-bracket"></i>
      Clock Out
    `;
    btn.style.background = "#dc2626";
  } else {
    btn.innerHTML = `
      <i class="fa-solid fa-right-to-bracket"></i>
      Clock In
    `;
    btn.style.background = "#008000";
  }
}

async function startClockFlow(type) {
  pendingType = type;
  capturedDataUrl = "";
  pendingLocationText = "";

  document.getElementById("camTitle").textContent =
    type === "in" ? "Clock In Capture" : "Clock Out Capture";

  document.getElementById("clockNote").value = "";
  document.getElementById("camMsg").textContent = "";

  const video = document.getElementById("camVideo");
  const canvas = document.getElementById("camCanvas");
  const placeholder = document.getElementById("cameraPlaceholder");

  if (video) video.style.display = "none";
  if (canvas) canvas.style.display = "none";
  if (placeholder) {
    placeholder.style.display = "flex";
    placeholder.textContent = "Camera loading...";
  }

  document.getElementById("camModal").style.display = "flex";
  document.getElementById("camMsg").textContent = "Getting location...";

navigator.geolocation.getCurrentPosition(
  async function(pos){

    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    pendingLocationText = lat + "," + lng;

    try{

      const geoRes = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`
      );

      const geoData = await geoRes.json();

      if(geoData.display_name){
        pendingLocationText = geoData.display_name;
      }
      document.getElementById("camMsg").textContent = "";

    }catch(err){
      console.log("Geocode failed", err);
    }

  },

  function(error){

    let text = "Location error.";

    if(error.code === 1){
      text = "Location denied. Please allow location permission.";
    }else if(error.code === 2){
      text = "Location unavailable.";
    }else if(error.code === 3){
      text = "Location timeout.";
    }

    document.getElementById("camMsg").textContent = text;
  },

  {
    enableHighAccuracy:true,
    timeout:15000,
    maximumAge:0
  }
);

await openCameraInput();
}

function stopCameraStream() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
}

function closeCamera() {
  stopCameraStream();
  document.getElementById("camModal").style.display = "none";
}

async function openCameraInput() {
  const msg = document.getElementById("camMsg");
  const video = document.getElementById("camVideo");
  const canvas = document.getElementById("camCanvas");
  const placeholder = document.getElementById("cameraPlaceholder");

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    msg.textContent = "Live camera is not supported on this browser.";
    return;
  }

  stopCameraStream();

  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({
  video: {
    facingMode: "user",
    width: { ideal: 1280 },
    height: { ideal: 720 }
  },
  audio: false
});

    video.srcObject = cameraStream;
    await video.play();

    video.style.display = "block";
    canvas.style.display = "none";
    placeholder.style.display = "none";

    msg.textContent = "";
  } catch (err) {
    console.log(err);
    msg.textContent = "Camera access denied or unavailable.";
  }
}

function retakePhoto() {
  capturedDataUrl = "";

  const video = document.getElementById("camVideo");
  const canvas = document.getElementById("camCanvas");
  const placeholder = document.getElementById("cameraPlaceholder");

  canvas.style.display = "none";
  video.style.display = "none";

  placeholder.style.display = "flex";
  placeholder.textContent = "Camera loading...";

  openCameraInput();
}

function handleCameraFile() {
  const msg = document.getElementById("camMsg");
  const video = document.getElementById("camVideo");
  const canvas = document.getElementById("camCanvas");
  const placeholder = document.getElementById("cameraPlaceholder");

  if (!video || !video.srcObject || !video.videoWidth || !video.videoHeight) {
    msg.textContent = "Camera is not ready yet.";
    return;
  }

  const ctx = canvas.getContext("2d");

  const vw = video.videoWidth;
  const vh = video.videoHeight;

  const maxW = 900;
  const scale = Math.min(1, maxW / vw);

  canvas.width = Math.round(vw * scale);
  canvas.height = Math.round(vh * scale);

  ctx.save();
  ctx.filter = "brightness(0.92) contrast(1.05)";
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  ctx.restore();

  capturedDataUrl = canvas.toDataURL("image/jpeg", 0.8);

  stopCameraStream();

  video.style.display = "none";
  placeholder.style.display = "none";
  canvas.style.display = "block";

  msg.textContent = "Photo captured.";
}

async function confirmClock() {
  const msg = document.getElementById("camMsg");

  if (!pendingType) {
    msg.textContent = "Clock type missing.";
    return;
  }

  if (!capturedDataUrl) {
    msg.textContent = "Please capture photo first.";
    return;
  }

  if (!pendingLocationText) {
    msg.textContent = "Location not ready yet. Please wait a moment.";
    return;
  }

  const notes = document.getElementById("clockNote").value.trim();

  msg.textContent = "Saving...";

  const formData = new FormData();
  formData.append("location", pendingLocationText);
  formData.append("notes", notes);
  formData.append("photo", capturedDataUrl);

  const apiUrl = pendingType === "in"
    ? "api/clock_in.php"
    : "api/clock_out.php";

  try {
    const res = await fetch(apiUrl, {
      method: "POST",
      body: formData
    });

    const data = await res.json();

    if (!data.ok) {
      msg.textContent = data.msg || "Failed";
      return;
    }

    msg.textContent = data.msg || "Success";

    await loadStaffStatus();

    setTimeout(function () {
      closeCamera();
    }, 700);

  } catch (e) {
    msg.textContent = "Server error: " + e.message;
  }
}

async function doLogout() {
  await fetch("api/logout.php");
  localStorage.removeItem("attendance_user");
  sessionStorage.removeItem("attendance_user");
  window.location.href = "index.php";
}

let durationTimer = null;

function startLiveDuration(clockIn) {
  clearInterval(durationTimer);

  if (!clockIn) {
    document.getElementById("attDur").textContent = "—";
    return;
  }

  function update() {
    const start = new Date(clockIn.replace(" ", "T"));
    const now = new Date();

    if (isNaN(start)) {
      document.getElementById("attDur").textContent = "—";
      return;
    }

    let diff = Math.floor((now - start) / 1000);

    if (diff < 0) diff = 0;

    const h = Math.floor(diff / 3600);
    diff %= 3600;

    const m = Math.floor(diff / 60);
    const s = diff % 60;

    document.getElementById("attDur").textContent =
      `${h}h ${m}m ${s}s`;
  }

  update();
  durationTimer = setInterval(update, 1000);
}

function calcDuration(clockIn, clockOut) {
  const start = new Date(clockIn.replace(" ", "T"));
  const end = new Date(clockOut.replace(" ", "T"));

  if (isNaN(start) || isNaN(end)) return "—";

  let diff = Math.floor((end - start) / 1000);
  if (diff < 0) return "—";

  const h = Math.floor(diff / 3600);
  diff %= 3600;

  const m = Math.floor(diff / 60);
  const s = diff % 60;

  return `${h}h ${m}m ${s}s`;
}

function setStatusColor(el, status) {
  if (!el) return;

  el.classList.remove("statusEarly", "statusOnTime", "statusLate", "statusAuto");

  const s = String(status || "").toUpperCase().trim();

  el.style.color = "";
  el.style.fontWeight = "900";

  if (s.includes("EARLY")) {
    el.classList.add("statusEarly");
    el.style.color = "#2563eb";
  } else if (s.includes("ON TIME")) {
    el.classList.add("statusOnTime");
    el.style.color = "#16a34a";
  } else if (s.includes("LATE")) {
    el.classList.add("statusLate");
    el.style.color = "#dc2626";
  } else if (s.includes("AUTO")) {
    el.classList.add("statusAuto");
    el.style.color = "#9333ea";
  }
}

async function toggleStaffLog() {
  const box = document.getElementById("staffLogBox");
  const btn = document.getElementById("staffLogBtn");

  const isHidden = box.classList.contains("hidden");

  if (isHidden) {
    box.classList.remove("hidden");
    btn.innerHTML = `<i class="fa-regular fa-calendar-xmark"></i> Close Log`;

    const dateInput = document.getElementById("staffLogDate");
    if (!dateInput.value) {
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth() + 1).padStart(2, "0");
      const dd = String(today.getDate()).padStart(2, "0");
      dateInput.value = `${yyyy}-${mm}-${dd}`;
    }

    await loadStaffLog();
  } else {
    box.classList.add("hidden");
    btn.innerHTML = `<i class="fa-regular fa-calendar-check"></i> View Log`;
  }
}

async function loadStaffLog() {
  const tbody = document.getElementById("staffLogTbody");
  const msg = document.getElementById("staffLogMsg");
  const date = document.getElementById("staffLogDate").value || "";

  tbody.innerHTML = `<tr><td colspan="11">Loading...</td></tr>`;
  msg.textContent = "";

  try {
    const res = await fetch("api/staff_log.php?date=" + encodeURIComponent(date), {
      credentials: "include"
    });

    const data = await res.json();

    if (!data.ok) {
      tbody.innerHTML = `<tr><td colspan="11">${data.msg || "Failed load log"}</td></tr>`;
      return;
    }

    if (!data.data.length) {
      tbody.innerHTML = `<tr><td colspan="11">No attendance log</td></tr>`;
      return;
    }

    tbody.innerHTML = data.data.map(r => `
      <tr>
        <td>${r.clock_in || "—"}</td>
        <td>${r.clock_out || "—"}</td>
        <td>${r.status_in || "—"}</td>
        <td>${r.status_out || "—"}</td>
        <td>${makeMapLink(r.location_in)}</td>
        <td>${makeMapLink(r.location_out)}</td>
        <td>${makePhotoLink(r.photo_in, "Photo In")}</td>
        <td>${makePhotoLink(r.photo_out, "Photo Out")}</td>
        <td>${r.notes_in || "—"}</td>
        <td>${r.notes_out || "—"}</td>
        <td>
          ${
            r.clock_in && r.clock_out
              ? calcDuration(r.clock_in, r.clock_out)
              : "—"
          }
        </td>
      </tr>
    `).join("");

    msg.textContent = `Total records: ${data.data.length}`;

  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="11">Server error</td></tr>`;
  }
}

function makeMapLink(loc) {
  if (!loc) return "—";

  const url = "https://www.google.com/maps?q=" + encodeURIComponent(loc);

  return `<a href="${url}" target="_blank" rel="noopener noreferrer">${loc}</a>`;
}

function makePhotoLink(path, text) {
  if (!path) return "—";
  return `<a href="${path}" target="_blank" rel="noopener noreferrer">${text}</a>`;
}