// ───────── Toast ─────────
function dismissToast() {
  const toast = document.getElementById("toast");
  if (!toast) return;

  toast.classList.add("hiding");
  setTimeout(() => toast.remove(), 300);
}

document.addEventListener("DOMContentLoaded", () => {
  const toast = document.getElementById("toast");
  if (toast) {
    setTimeout(dismissToast, 1200);
  }
});

// ───────── Delete Modal ─────────
function openDelete(id, name) {
  document.getElementById("deleteIdField").value = id;
  document.getElementById("delItemName").textContent = name;

  document.getElementById("deleteOverlay").classList.add("open");
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("deleteOverlay").classList.remove("open");
  document.body.style.overflow = "";
}

function showSpinner() {
  document.getElementById("delSpinner").style.display = "block";
  document.getElementById("delBtnText").textContent = "Deleting…";
}

// Close on ESC
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeModal();
});

// Close on backdrop click
document.addEventListener("click", (e) => {
  const overlay = document.getElementById("deleteOverlay");
  if (overlay && e.target === overlay) closeModal();
});

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".act-delete").forEach((btn) => {
    btn.addEventListener("click", function () {
      openDelete(this.dataset.id, this.dataset.name);
    });
  });
});
