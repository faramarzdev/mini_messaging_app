import { useEffect, useState } from "react";
import { NavLink } from "react-router-dom";
import ChatProfile from "../../components/app/ChatProfile";
import api from "../../config/api";
import LoadingSpinner from "../../components/LoadingSpinner";

export default function SideNavbar({ collapsed, darkMode }) {
  const [navItems, setNavItems] = useState([]);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadData = async () => {
      try {
        const response = await api.get(`/chats`);
        setNavItems(response.data.data);
      } catch (err) {
        setError(err.response?.data?.message || "Failed to load post");
      } finally {
        setLoading(false);
      }
    };
    loadData();
  });
  
  if (loading) return <LoadingSpinner />;

  return (
    <nav className="flex-1 overflow-y-auto py-4 px-2 space-y-1">
      {navItems.map((user) => (
        <NavLink
          key={user.id}
          to={`/app/chat/${user.id}`}
          className={({ isActive }) =>
            `flex items-center rounded-lg text-sm font-medium transition-all duration-150 group
                ${collapsed ? "justify-center px-0 py-2" : "gap-3 px-3 py-1.5"}
                ${
                  isActive
                    ? darkMode
                      ? "bg-blue-600/30 text-blue-300 ring-1 ring-blue-500/40"
                      : "bg-blue-500/20 text-blue-400 ring-1 ring-blue-500/30"
                    : darkMode
                      ? "text-slate-400 hover:bg-slate-800/60 hover:text-slate-100"
                      : "text-slate-400 hover:bg-slate-800 hover:text-slate-100"
                }`
          }
        >
          <ChatProfile user={user} collapsed={collapsed} />
        </NavLink>
      ))}
    </nav>
  );
}
