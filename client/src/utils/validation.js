export const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const isValidEmail = (email) => EMAIL_REGEX.test(email);

export const MIN_PASSWORD_LENGTH = 8;

export const isValidPassword = (password) =>
  Boolean(password) && password.length >= MIN_PASSWORD_LENGTH;
