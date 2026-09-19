export const ROUTES = {
  home: "/",

  auth: "/auth",
  authLogin: "/auth/login",
  authRegister: "/auth/register",
  authForgotPassword: "/auth/forgot-password",
  authResetPassword: "/auth/reset-password",

  app: "/app",
  appContacts: "/app/contacts",

  appProfile: "/app/profile/:handle",
  profilePath: (handle) => `/app/profile/${handle}`,

  appProfileMessage: "/app/profile/:handle/message",
  profilePathMessage: (handle) => `/app/profile/${handle}/message`,
};
