import "./bootstrap";

import "./csrf-fetch";

import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";

import { registerAttendanceEvidenceViewer } from "./attendance-evidence-viewer.js";
import { initToastStack } from "./toast.js";
import SidebarController from "./sidebar.js";

Alpine.plugin(collapse);
registerAttendanceEvidenceViewer(Alpine);

window.Alpine = Alpine;

Alpine.start();

window.toggleSidebar = () => SidebarController.toggle();

function loadPageModule(importer, initialize = null) {
  importer()
    .then((module) => initialize?.(module))
    .catch((error) => {
      console.error("Gagal memuat modul halaman:", error);
    });
}

function bootPageModules() {
  SidebarController.init();

  initToastStack();

  if (document.getElementById("leave-verify-confirm-modal")) {
    loadPageModule(
      () => import("./leave-verification.js"),
      (module) => module.default.init(),
    );
  }

  if (document.getElementById("create-modal")) {
    loadPageModule(
      () => import("./employees.js"),
      (module) => module.default.init(),
    );
  }

  if (document.getElementById("calendar-edit-modal")) {
    loadPageModule(
      () => import("./work-calendar.js"),
      (module) => module.default.init(),
    );
  }

  if (
    document.getElementById("manual-attendance-create-modal")
    || document.getElementById("manual-attendance-edit-modal")
  ) {
    loadPageModule(
      () => import("./admin-manual-attendance.js"),
      (module) => module.default.init(),
    );
  }

  if (document.getElementById("admin-attendance-detail-modal")) {
    loadPageModule(() => import("./admin-attendance-detail.js"));
  }

  if (document.getElementById("whatsapp-report-modal")) {
    loadPageModule(() => import("./admin-attendance-whatsapp-report.js"));
  }

  if (
    document.getElementById("weeklyChart") ||
    document.getElementById("monthlyChart") ||
    document.getElementById("statusChart")
  ) {
    loadPageModule(
      () => import("./dashboard.js"),
      (module) => module.default.init(),
    );
  }

  if (document.getElementById("attendance-page-root")) {
    loadPageModule(
      () => import("./attendance-page.js"),
      (module) => module.initAttendancePage(),
    );
    loadPageModule(() => import("./attendance-face-verification.js"));
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", bootPageModules);
} else {
  bootPageModules();
}
