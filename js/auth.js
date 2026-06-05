document.addEventListener("DOMContentLoaded", function(){
  loadUsers();
});

async function loadUsers(){
  const select = document.getElementById("userId");

  try{
    const res = await fetch("api/users.php");
    const data = await res.json();

    if(!data.ok){
      select.innerHTML = `<option value="">Error load users</option>`;
      return;
    }

    let html = `<option value="">-- Choose ID --</option>`;

    data.data.forEach(u => {
      html += `<option value="${u.staff_id}" data-role="${u.role}">
        ${u.staff_id} - ${u.name}
      </option>`;
    });

    select.innerHTML = html;

    $('#userId').select2({
  placeholder: "Search ID / Name",
  width: '100%'
});

  }catch(e){
    select.innerHTML = `<option value="">Failed load</option>`;
  }
}

async function login(){
  const id = document.getElementById("userId").value;
  const password = document.getElementById("password").value;
  const msg = document.getElementById("msg");

  if(!id || !password){
    msg.textContent = "Please fill ID & password";
    return;
  }

  msg.textContent = "Logging in...";

  const formData = new FormData();
  formData.append("id", id);
  formData.append("password", password);
  formData.append(
  "remember",
  document.getElementById("rememberMe").checked ? "on" : ""
);

  try{
    const res = await fetch("api/login.php", {
  method: "POST",
  body: formData,
  credentials: "include" // 🔥 INI WAJIB
});
    const data = await res.json();

    if(!data.ok){
      msg.textContent = data.msg;
      return;
    }


    if(data.role === "admin"){
      window.location.href = "admin.php";
    }else{
      window.location.href = "staff.php";
    }

  }catch(e){
    msg.textContent = "Server error";
  }
}