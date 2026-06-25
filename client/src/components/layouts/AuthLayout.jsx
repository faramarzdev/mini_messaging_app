import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";

export default function AuthLayout() {
  const { user, loading } = useAuth();

  if (loading) return null;

  if (user) return <Navigate to="/" replace />;

  return (
    <div className="h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
      <div className="min-h-[calc(100vh-16rem)] flex items-center justify-center px-4">
        <div className="w-full max-w-md">
          <div className="bg-white dark:bg-gray-800 rounded-2xl border border-stone-200 dark:border-gray-700 shadow-xl p-8">
            <Outlet />
          </div>
        </div>
      </div>
    </div>
  );
}
