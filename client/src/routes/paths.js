export const ROUTES = {
  home: "/",

  auth: "/auth",
  authLogin: "/auth/login",
  authRegister: "/auth/register",
  authForgotPassword: "/auth/forgot-password",
  authResetPassword: "/auth/reset-password",

  app: "/app",
  appContacts: "/app/contacts",
  appChat: "/app/chat/:id",
  chatPath: (id) => `/app/chat/${id}`,

  appProfile: "/app/profile/:id",
  profilePath: (id) => `/app/profile/${id}`,
};
