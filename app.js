/* =====================================================================
   PORTFOLIO — logique applicative
   Les projets publics sont servis par api.php. Les actions d'administration
   sont protégées côté interface et vérifiées à nouveau côté serveur.
   ===================================================================== */

const API_URL = "api.php";
const STATIC_MODE = window.location.hostname.endsWith("github.io");
const STATIC_PROJECTS_KEY = "portfolio-projects-local";
const SUPABASE_CONFIG = window.SUPABASE_CONFIG || {};
const SUPABASE_ENABLED = Boolean(
  window.supabase?.createClient &&
  SUPABASE_CONFIG.url &&
  SUPABASE_CONFIG.anonKey &&
  !SUPABASE_CONFIG.url.includes("TON-PROJET") &&
  !SUPABASE_CONFIG.anonKey.includes("TA_CLE")
);
const supabaseClient = SUPABASE_ENABLED
  ? window.supabase.createClient(SUPABASE_CONFIG.url, SUPABASE_CONFIG.anonKey)
  : null;
let currentDetailId = null;
let filesBuffer = []; // fichiers en attente dans le formulaire d'ajout
let adminPassword = "";
let adminAccessResolve = null;

/* ---------------------------------------------------------------
   0. API et accès administrateur
   --------------------------------------------------------------- */
async function apiRequest(options = {}) {
  if (STATIC_MODE) return staticRequest(options);
  const response = await fetch(API_URL, options);
  const responseText = await response.text();
  let data;
  try {
    data = JSON.parse(responseText);
  } catch {
    throw new Error("Le serveur PHP a refusé l'envoi. Le dossier est peut-être trop volumineux.");
  }
  if (!response.ok || data.error) throw new Error(data.error || "Erreur serveur.");
  return data;
}

async function dbGetAll() {
  if (SUPABASE_ENABLED) {
    const { data, error } = await supabaseClient
      .from("projects")
      .select("*")
      .order("date", { ascending: false });
    if (error) throw new Error(error.message);
    return data || [];
  }
  if (STATIC_MODE) {
    const stored = localStorage.getItem(STATIC_PROJECTS_KEY);
    if (stored) return JSON.parse(stored);

    const response = await fetch("data/projects.json");
    if (!response.ok) throw new Error("Impossible de charger les projets publics.");
    const projects = await response.json();
    localStorage.setItem(STATIC_PROJECTS_KEY, JSON.stringify(projects));
    return projects;
  }
  return apiRequest();
}

async function staticRequest(options = {}) {
  const payload = options.body;
  if (!(payload instanceof FormData)) {
    const request = JSON.parse(payload || "{}");
    if (request.action === "delete") {
      const projects = await dbGetAll();
      const filtered = projects.filter((project) => project.id !== request.id);
      localStorage.setItem(STATIC_PROJECTS_KEY, JSON.stringify(filtered));
      return { ok: true };
    }
    return { error: "Cette action nécessite un serveur PHP." };
  }

  const projects = await dbGetAll();
  const project = {
    id: payload.get("id"),
    title: payload.get("title"),
    description: payload.get("description"),
    tags: JSON.parse(payload.get("tags") || "[]"),
    date: payload.get("date"),
    link: payload.get("link"),
    createdAt: Number(payload.get("createdAt")),
    files: [],
  };
  const existing = projects.find((item) => item.id === project.id);
  project.files = existing?.files || [];
  for (const [key, value] of payload.entries()) {
    if (key !== "files[]" || !(value instanceof File)) continue;
    project.files.push({
      name: value.name,
      path: value.name,
      type: value.type,
      size: value.size,
      url: URL.createObjectURL(value),
    });
  }
  const updated = existing
    ? projects.map((item) => (item.id === project.id ? project : item))
    : [...projects, project];
  localStorage.setItem(STATIC_PROJECTS_KEY, JSON.stringify(updated));
  return { ok: true, project };
}

function requestAdminAccess() {
  const overlay = document.getElementById("admin-overlay");
  const form = document.getElementById("admin-form");
  const input = document.getElementById("admin-password");
  overlay.hidden = false;
  input.value = "";
  input.focus();

  return new Promise((resolve) => {
    adminAccessResolve = resolve;
    form.onsubmit = (event) => {
      event.preventDefault();
      adminPassword = input.value;
      overlay.hidden = true;
      adminAccessResolve?.(true);
      adminAccessResolve = null;
    };
  });
}

function cancelAdminAccess() {
  document.getElementById("admin-overlay").hidden = true;
  adminAccessResolve?.(false);
  adminAccessResolve = null;
}

document.getElementById("admin-cancel").addEventListener("click", cancelAdminAccess);
document.getElementById("admin-close").addEventListener("click", cancelAdminAccess);
document.getElementById("admin-overlay").addEventListener("click", (event) => {
  if (event.target.id === "admin-overlay") cancelAdminAccess();
});

async function dbPut(project) {
  if (SUPABASE_ENABLED) return supabasePut(project);

  const newFiles = project.files.filter((file) => file.blob instanceof Blob);
  const batches = [];
  let batch = [];
  let batchSize = 0;
  const maxBatchFiles = 15;
  const maxBatchSize = 5.5 * 1024 * 1024;

  newFiles.forEach((file) => {
    if (batch.length && (batch.length >= maxBatchFiles || batchSize + file.size > maxBatchSize)) {
      batches.push(batch);
      batch = [];
      batchSize = 0;
    }
    batch.push(file);
    batchSize += file.size;
  });
  if (batch.length || batches.length === 0) batches.push(batch);

  let result;
  for (const files of batches) {
    const payload = new FormData();
    payload.append("action", "save");
    payload.append("password", adminPassword);
    payload.append("id", project.id);
    payload.append("title", project.title);
    payload.append("description", project.description);
    payload.append("tags", JSON.stringify(project.tags));
    payload.append("date", project.date);
    payload.append("link", project.link);
    payload.append("createdAt", String(project.createdAt));

    files.forEach((file) => {
      payload.append("files[]", file.blob, file.path || file.name);
      payload.append("paths[]", file.path || file.name);
    });

    result = await apiRequest({ method: "POST", body: payload });
  }
  return result;
}

async function dbDelete(id) {
  if (SUPABASE_ENABLED) {
    const project = (await dbGetAll()).find((item) => item.id === id);
    if (project) {
      const paths = (project.files || []).map((file) => file.storagePath).filter(Boolean);
      if (paths.length) await supabaseClient.storage.from("project-files").remove(paths);
    }
    const { error } = await supabaseClient.from("projects").delete().eq("id", id);
    if (error) throw new Error(error.message);
    return { ok: true };
  }
  return apiRequest({
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "delete", id, password: adminPassword }),
  });
}

async function supabasePut(project) {
  const files = [];
  for (const file of project.files) {
    if (!(file.blob instanceof Blob)) {
      files.push(file);
      continue;
    }

    const relativePath = file.path || file.name;
    const storagePath = `${project.id}/${relativePath}`;
    const { error } = await supabaseClient.storage
      .from("project-files")
      .upload(storagePath, file.blob, { upsert: true, contentType: file.type || undefined });
    if (error) throw new Error(error.message);

    const { data } = supabaseClient.storage.from("project-files").getPublicUrl(storagePath);
    files.push({
      name: file.name,
      path: relativePath,
      type: file.type,
      size: file.size,
      storagePath,
      url: data.publicUrl,
    });
  }

  const record = { ...project, files };
  delete record.blob;
  const { error } = await supabaseClient.from("projects").upsert(record);
  if (error) throw new Error(error.message);
  return { ok: true, project: record };
}

/* ---------------------------------------------------------------
   1. Utilitaires
   --------------------------------------------------------------- */
function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
}

function humanSize(bytes) {
  if (bytes < 1024) return bytes + " o";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " ko";
  return (bytes / (1024 * 1024)).toFixed(1) + " Mo";
}

function extOf(name) {
  const parts = name.split(".");
  return parts.length > 1 ? parts.pop().toUpperCase() : "FILE";
}

function formatMonth(value) {
  if (!value) return "";
  const [y, m] = value.split("-");
  const months = ["janv.", "févr.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc."];
  return `${months[parseInt(m, 10) - 1]} ${y}`;
}

function escapeHTML(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

function showToast(msg) {
  const toast = document.getElementById("toast");
  toast.textContent = msg;
  toast.hidden = false;
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => (toast.hidden = true), 2400);
}

/* ---------------------------------------------------------------
   2. Rendu de la grille de projets
   --------------------------------------------------------------- */
async function renderProjects() {
  const all = await dbGetAll();
  all.sort((a, b) => (b.date || "").localeCompare(a.date || "") || b.createdAt - a.createdAt);

  populateTagFilter(all);

  const search = document.getElementById("search-input").value.trim().toLowerCase();
  const tagFilter = document.getElementById("tag-filter").value;

  const filtered = all.filter((p) => {
    const matchesSearch =
      !search ||
      p.title.toLowerCase().includes(search) ||
      p.description.toLowerCase().includes(search) ||
      p.tags.some((t) => t.toLowerCase().includes(search));
    const matchesTag = !tagFilter || p.tags.includes(tagFilter);
    return matchesSearch && matchesTag;
  });

  const grid = document.getElementById("projects-grid");
  const emptyState = document.getElementById("empty-state");
  grid.innerHTML = "";

  document.getElementById("tb-count").textContent = String(all.length).padStart(3, "0");

  if (filtered.length === 0) {
    emptyState.hidden = false;
  } else {
    emptyState.hidden = true;
    filtered.forEach((p, i) => grid.appendChild(buildCard(p, i, all.length)));
  }
}

function populateTagFilter(all) {
  const select = document.getElementById("tag-filter");
  const current = select.value;
  const tags = [...new Set(all.flatMap((p) => p.tags))].sort();
  select.innerHTML = '<option value="">Toutes les technos</option>';
  tags.forEach((t) => {
    const opt = document.createElement("option");
    opt.value = t;
    opt.textContent = t;
    select.appendChild(opt);
  });
  if (tags.includes(current)) select.value = current;
}

function buildCard(project, index, total) {
  const card = document.createElement("article");
  card.className = "project-card";
  card.tabIndex = 0;
  card.setAttribute("role", "button");
  card.setAttribute("aria-label", `Voir le projet ${project.title}`);

  const idx = String(total - index).padStart(3, "0");

  card.innerHTML = `
    <span class="pc-index">N° ${idx}</span>
    <h3 class="pc-title">${escapeHTML(project.title)}</h3>
    <p class="pc-desc">${escapeHTML(project.description)}</p>
    <div class="pc-tags">
      ${project.tags.map((t) => `<span class="pc-tag">${escapeHTML(t)}</span>`).join("")}
    </div>
    <div class="pc-foot">
      <span>${project.date ? formatMonth(project.date) : "—"}</span>
      <span class="pc-files">${project.files.length} fichier${project.files.length > 1 ? "s" : ""}</span>
    </div>
  `;

  const open = () => openDetail(project.id);
  card.addEventListener("click", open);
  card.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      open();
    }
  });

  return card;
}

/* ---------------------------------------------------------------
   3. Modale d'ajout / édition
   --------------------------------------------------------------- */
const modalOverlay = document.getElementById("modal-overlay");
const form = document.getElementById("project-form");
const dropzone = document.getElementById("dropzone");
const fileInput = document.getElementById("f-files");
const folderInput = document.getElementById("f-folder");
const fileListEl = document.getElementById("file-list");

async function openModal(project = null, alreadyAuthorized = false) {
  if (!alreadyAuthorized && !(await requestAdminAccess())) return;

  form.reset();
  filesBuffer = project ? [...project.files] : [];
  renderFileList();

  document.getElementById("modal-title").textContent = project ? "Modifier le projet" : "Nouveau projet";
  document.getElementById("project-id").value = project ? project.id : "";
  document.getElementById("f-title").value = project ? project.title : "";
  document.getElementById("f-desc").value = project ? project.description : "";
  document.getElementById("f-tags").value = project ? project.tags.join(", ") : "";
  document.getElementById("f-date").value = project ? project.date || "" : "";
  document.getElementById("f-link").value = project ? project.link || "" : "";

  modalOverlay.hidden = false;
  document.getElementById("f-title").focus();
}

function closeModal() {
  modalOverlay.hidden = true;
  filesBuffer = [];
}

document.querySelectorAll("[data-open-modal]").forEach((btn) =>
  btn.addEventListener("click", () => openModal())
);
document.getElementById("modal-close").addEventListener("click", closeModal);
document.getElementById("btn-cancel").addEventListener("click", closeModal);
modalOverlay.addEventListener("click", (e) => {
  if (e.target === modalOverlay) closeModal();
});

/* --- zone de dépôt de fichiers (tous types : pdf, code, zip...) --- */
document.getElementById("choose-files").addEventListener("click", () => fileInput.click());
document.getElementById("choose-folder").addEventListener("click", chooseFolder);
dropzone.addEventListener("keydown", (e) => {
  if (e.key === "Enter" || e.key === " ") {
    e.preventDefault();
    folderInput.click();
  }
});
fileInput.addEventListener("change", () => addFiles(fileInput.files));
folderInput.addEventListener("change", () => addFiles(folderInput.files));

async function chooseFolder() {
  if (typeof window.showDirectoryPicker !== "function") {
    folderInput.click();
    return;
  }

  try {
    const directory = await window.showDirectoryPicker({ mode: "read" });
    const files = [];
    await collectDirectoryFiles(directory, directory.name, files);
    addFiles(files);
  } catch (error) {
    if (error.name !== "AbortError") showToast("Impossible de lire ce dossier.");
  }
}

async function collectDirectoryFiles(directory, currentPath, files) {
  for await (const entry of directory.values()) {
    const entryPath = `${currentPath}/${entry.name}`;
    if (entry.kind === "file") {
      const file = await entry.getFile();
      files.push({ file, path: entryPath });
    } else if (entry.kind === "directory") {
      await collectDirectoryFiles(entry, entryPath, files);
    }
  }
}

["dragenter", "dragover"].forEach((evt) =>
  dropzone.addEventListener(evt, (e) => {
    e.preventDefault();
    dropzone.classList.add("drag");
  })
);
["dragleave", "drop"].forEach((evt) =>
  dropzone.addEventListener(evt, (e) => {
    e.preventDefault();
    dropzone.classList.remove("drag");
  })
);
dropzone.addEventListener("drop", (e) => addFiles(e.dataTransfer.files));

function addFiles(fileListInput) {
  Array.from(fileListInput).forEach((fileEntry) => {
    const file = fileEntry.file || fileEntry;
    const path = fileEntry.path || file.webkitRelativePath || file.relativePath || file.name;
    const duplicate = filesBuffer.some((existing) => existing.path === path && existing.size === file.size);
    if (!duplicate) {
      filesBuffer.push({
        name: file.name,
        path,
        type: file.type,
        size: file.size,
        blob: file,
      });
    }
  });
  renderFileList();
  fileInput.value = "";
  folderInput.value = "";
}

function renderFileList() {
  fileListEl.innerHTML = "";
  filesBuffer.forEach((f, i) => {
    const li = document.createElement("li");
    const displayPath = f.path || f.name;
    li.innerHTML = `
      <span title="${escapeHTML(displayPath)}">${escapeHTML(displayPath)} · ${humanSize(f.size)}</span>
      <button type="button" class="f-remove" aria-label="Retirer ${escapeHTML(f.name)}">✕</button>
    `;
    li.querySelector(".f-remove").addEventListener("click", () => {
      filesBuffer.splice(i, 1);
      renderFileList();
    });
    fileListEl.appendChild(li);
  });
}

/* --- soumission du formulaire --- */
form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const id = document.getElementById("project-id").value || uid();
  const existing = document.getElementById("project-id").value;

  const project = {
    id,
    title: document.getElementById("f-title").value.trim(),
    description: document.getElementById("f-desc").value.trim(),
    tags: document
      .getElementById("f-tags")
      .value.split(",")
      .map((t) => t.trim())
      .filter(Boolean),
    date: document.getElementById("f-date").value,
    link: document.getElementById("f-link").value.trim(),
    files: filesBuffer,
    createdAt: existing ? (await getProject(existing)).createdAt : Date.now(),
  };

  try {
    await dbPut(project);
    closeModal();
    await renderProjects();
    showToast(
      SUPABASE_ENABLED
        ? "Projet enregistré définitivement."
        : STATIC_MODE
          ? "Projet enregistré sur ce navigateur uniquement."
        : existing
          ? "Projet mis à jour."
          : "Projet ajouté au portfolio."
    );
  } catch (err) {
    showToast(err.message);
  }
});

async function getProject(id) {
  const all = await dbGetAll();
  return all.find((p) => p.id === id);
}

/* ---------------------------------------------------------------
   4. Modale de détail
   --------------------------------------------------------------- */
const detailOverlay = document.getElementById("detail-overlay");

async function openDetail(id) {
  const project = await getProject(id);
  if (!project) return;
  currentDetailId = id;

  document.getElementById("detail-title").textContent = project.title;
  document.getElementById("detail-date").textContent = project.date ? formatMonth(project.date) : "Date non précisée";
  document.getElementById("detail-desc").textContent = project.description;

  const linkEl = document.getElementById("detail-link");
  if (project.link) {
    linkEl.href = project.link;
    linkEl.hidden = false;
  } else {
    linkEl.hidden = true;
  }

  document.getElementById("detail-tags").innerHTML = project.tags
    .map((t) => `<span class="pc-tag">${escapeHTML(t)}</span>`)
    .join("");

  const fileList = document.getElementById("detail-file-list");
  fileList.innerHTML = "";
  if (project.files.length === 0) {
    fileList.innerHTML = `<li><span class="df-name">Aucun fichier joint</span></li>`;
  } else {
    project.files.forEach((f) => {
      const url = f.url;
      const displayPath = f.path || f.name;
      const li = document.createElement("li");
      li.innerHTML = `
        <span class="df-name"><span class="df-ext">${extOf(f.name)}</span> ${escapeHTML(displayPath)} <span class="df-size">${humanSize(f.size)}</span></span>
        <a class="df-download" href="${url}" download="${escapeHTML(f.name)}">Télécharger</a>
      `;
      fileList.appendChild(li);
    });
  }

  detailOverlay.hidden = false;
}

function closeDetail() {
  detailOverlay.hidden = true;
  currentDetailId = null;
}

document.getElementById("detail-close").addEventListener("click", closeDetail);
detailOverlay.addEventListener("click", (e) => {
  if (e.target === detailOverlay) closeDetail();
});

document.getElementById("detail-edit").addEventListener("click", async () => {
  if (!(await requestAdminAccess())) return;
  const project = await getProject(currentDetailId);
  closeDetail();
  openModal(project, true);
});

document.getElementById("detail-delete").addEventListener("click", async () => {
  if (!(await requestAdminAccess())) return;
  if (!confirm("Supprimer définitivement ce projet et ses fichiers ?")) return;
  try {
    await dbDelete(currentDetailId);
    closeDetail();
    await renderProjects();
    showToast("Projet supprimé.");
  } catch (err) {
    showToast(err.message);
  }
});

/* ---------------------------------------------------------------
   5. Filtres (recherche + techno)
   --------------------------------------------------------------- */
document.getElementById("search-input").addEventListener("input", renderProjects);
document.getElementById("tag-filter").addEventListener("change", renderProjects);

/* ---------------------------------------------------------------
   6. Fermeture générale à l'échappement
   --------------------------------------------------------------- */
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    if (!document.getElementById("admin-overlay").hidden) cancelAdminAccess();
    if (!modalOverlay.hidden) closeModal();
    if (!detailOverlay.hidden) closeDetail();
  }
});

/* ---------------------------------------------------------------
   7. Cartouche : date du jour
   --------------------------------------------------------------- */
function setTitleBlockDate() {
  const now = new Date();
  document.getElementById("tb-date").textContent = now.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

/* ---------------------------------------------------------------
   8. Animation d'entrée du titre (une seule fois, au chargement)
   --------------------------------------------------------------- */
function revealHero() {
  const lines = document.querySelectorAll(".hero-title .line");
  lines.forEach((line, i) => {
    const text = line.textContent.trim();
    const span = document.createElement("span");
    span.className = line.classList.contains("name-highlight") ? "typewriter-text" : "";
    span.style.transform = "translateY(110%)";
    span.style.transition = `transform .6s cubic-bezier(.2,.8,.2,1) ${i * 0.08 + 0.1}s`;
    line.textContent = "";
    line.appendChild(span);
    requestAnimationFrame(() => requestAnimationFrame(() => {
      span.style.transform = "translateY(0)";

      if (!line.classList.contains("name-highlight")) {
        span.textContent = text;
        return;
      }

      line.setAttribute("aria-label", text);
      line.classList.add("is-typing");

      if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        span.textContent = text;
        line.classList.remove("is-typing");
        return;
      }

      let characterIndex = 0;
      const timer = setInterval(() => {
        characterIndex += 1;
        span.textContent = text.slice(0, characterIndex);
        if (characterIndex === text.length) {
          clearInterval(timer);
          line.classList.remove("is-typing");
        }
      }, 95);
    }));
  });
}

/* ---------------------------------------------------------------
   INIT
   --------------------------------------------------------------- */
(async function init() {
  setTitleBlockDate();
  revealHero();
  try {
    await renderProjects();
  } catch (err) {
    console.error("API indisponible :", err);
    showToast("Le portfolio n'arrive pas à joindre le serveur.");
  }
})();
