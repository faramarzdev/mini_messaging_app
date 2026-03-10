import { createContext, useState, useContext, useEffect } from "react";
import api from "../config/api";

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem("token"));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token) {
      api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
      // Only fetch user on initial load (page refresh)
      // Login/register set user directly from response
      if (!user) {
        fetchUser();
      } else {
        setLoading(false);
      }
    } else {
      setLoading(false);
    }
  }, [token]); // eslint-disable-line react-hooks/exhaustive-deps

  const fetchUser = async () => {
    try {
      const response = await api.get("/me");
      setUser(response.data.user);
    } catch (error) {
      localStorage.removeItem("token");
      setToken(null);
      delete api.defaults.headers.common["Authorization"];
    } finally {
      setLoading(false);
    }
  };

  const login = async (credentials) => {
    try {
      const response = await api.post("/login", credentials);

      // Set header before state updates
      api.defaults.headers.common["Authorization"] =
        `Bearer ${response.data.token}`;

      localStorage.setItem("token", response.data.token);
      setToken(response.data.token);
      setUser(response.data.user); // ← Direct set, no fetchUser needed!

      return response.data;
    } catch (error) {
      throw error;
    }
  };

  const register = async (userData) => {
    try {
      const response = await api.post("/register", userData);

      api.defaults.headers.common["Authorization"] =
        `Bearer ${response.data.token}`;

      localStorage.setItem("token", response.data.token);
      setToken(response.data.token);
      setUser(response.data.user); // ← Direct set!

      return response.data;
    } catch (error) {
      throw error;
    }
  };

  const logout = async () => {
    try {
      await api.post("/logout");
    } catch (error) {
      console.error("Logout API call failed:", error);
    } finally {
      localStorage.removeItem("token");
      setToken(null);
      setUser(null);
      delete api.defaults.headers.common["Authorization"];
    }
  };

  const value = {
    user,
    token,
    loading,
    isAuthenticated: !!user,
    login,
    register,
    logout,
  };

  return (
    <AuthContext.Provider value={value}>
      {loading ? (
        <div
          style={{
            display: "flex",
            justifyContent: "center",
            alignItems: "center",
            minHeight: "100vh",
          }}
        >
          Loading...
        </div>
      ) : (
        children
      )}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return context;
};
