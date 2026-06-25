import { createContext, useContext, useState, useEffect } from "react";
import {
  loginRequest,
  registerRequest,
  logoutRequest,
  getMe,
} from "../api/auth";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchUser = async () => {
    const token = localStorage.getItem("auth_token");
    if (!token) {
      setLoading(false);
      return;
    }
    try {
      const userData = await getMe();
      setUser(userData);
    } catch {
      localStorage.removeItem("auth_token");
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    const { token, user: userData } = await loginRequest(email, password);
    localStorage.setItem("auth_token", token);
    setUser(userData);
    return userData;
  };

  const register = async (name, email, password, passwordConfirmation) => {
    const { token, user: userData } = await registerRequest({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    });
    localStorage.setItem("auth_token", token);
    setUser(userData);
    return userData;
  };

  const logout = async () => {
    await logoutRequest();
    localStorage.removeItem("auth_token");
    setUser(null);
  };

  useEffect(() => {
    fetchUser();
  }, []);

  return (
    <AuthContext.Provider
      value={{ user, loading, login, register, logout, fetchUser }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used within AuthProvider");
  return context;
};
