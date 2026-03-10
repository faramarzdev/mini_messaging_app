import { useState } from "react";
import { Outlet } from "react-router-dom";
import SideNavbar from "./SideNavbar";
import DarkModeToggle from "./DarkModeToggle";
import Topbar from "./Topbar";
import SideBrand from "./SideBrand";
import SideCollapseToggle from "./SideCollapseToggle";
import { useTheme } from "../../context/ThemeContext";

export default function AppLayout() {
  const [collapsed, setCollapsed] = useState(false);
  const { darkMode, setDarkMode } = useTheme();

  return (
    <div className={`flex h-screen overflow-hidden ${darkMode ? "dark" : ""}`}>
      {/* ── Sidebar ───────────────────────────────────────────────────── */}
      <aside
        className={`
          flex flex-col shrink-0
          transition-all duration-300 ease-in-out
          ${collapsed ? "w-[68px]" : "w-64"}
          ${darkMode ? "bg-slate-950 text-slate-100" : "bg-slate-900 text-slate-100"}
        `}
      >
        {/* Brand */}
        <SideBrand collapsed={collapsed} darkMode={darkMode} />

        {/* Nav links */}
        <SideNavbar collapsed={collapsed} darkMode={darkMode} />

        {/* Side footer: Dark mode toggle + Collapse */}
        <div
          className={`shrink-0 p-2 space-y-1 ${darkMode ? "border-t border-slate-800" : "border-t border-slate-700/60"}`}
        >
          <DarkModeToggle
            collapsed={collapsed}
            darkMode={darkMode}
            setDarkMode={setDarkMode}
          />

          <SideCollapseToggle
            collapsed={collapsed}
            darkMode={darkMode}
            setCollapsed={setCollapsed}
          />
        </div>
      </aside>

      <div
        className={`flex flex-col flex-1 min-w-0 overflow-hidden ${darkMode ? "bg-slate-900" : "bg-slate-100"}`}
      >
        <Topbar darkMode={darkMode} />

        {/* Page content */}
        <main className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
