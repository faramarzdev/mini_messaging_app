import { Navigate, Outlet } from "react-router-dom";
import Sidebar from "../Sidebar";
import { useAuth } from "../../context/AuthContext";

export default function AppLayout() {
  const { user, loading } = useAuth();

  if (loading) return null;

  if (!user) return <Navigate to="/auth/login" replace />;

  return (
    <div className="flex h-full w-full overflow-hidden">
      <Sidebar />
      <main className="flex flex-1 min-w-0 overflow-hidden relative">
        <Outlet />
      </main>
    </div>
  );
}
