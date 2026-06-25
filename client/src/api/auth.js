import api from "./client";

export const loginRequest = (email, password) =>
  api.post("/login", { email, password }).then((r) => r.data);

/** data must be object including:  name, email, password, password_confirmation */
export const registerRequest = (data) =>
  api.post("/register", data).then((r) => r.data);

export const logoutRequest = () => api.post("/logout").then((r) => r.data);

export const getMe = () => api.get("/me").then((r) => r.data);
