import { Link } from "react-router-dom";

export default function Error404() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50">
      <div className="text-center">
        <h1 className="text-6xl font-bold text-amber-800 mb-4">404</h1>
        <p className="text-xl text-amber-600 mb-8">Page not found</p>
      </div>
    </div>
  );
}
