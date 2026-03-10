import { Outlet } from "react-router-dom";
import ErrorBoundary from "../../components/ErrorBoundary";
import { useTheme } from "../../context/ThemeContext";
import Footer from "./Footer";
import Header from "./Header";

export default function PublicLayout() {
  const { darkMode } = useTheme();

  return (
    <div className={darkMode ? "dark" : ""}>
      <div className="min-h-screen dark:bg-zinc-900 bg-stone-50 transition-colors duration-300">
        <Header />

        <main className="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl py-12">
          <ErrorBoundary>
            <Outlet />
          </ErrorBoundary>
        </main>

        <Footer />
      </div>
    </div>
  );
}
