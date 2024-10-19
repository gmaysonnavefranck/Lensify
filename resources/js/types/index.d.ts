import { Config } from 'ziggy-js';

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  auth: {
    user: User;
  };
  ziggy: Config & { location: string };
};

export type Author = {
  id?: number;
  name: string;
  themes?: string[];
  genres?: string[];
  famous_works?: string[];
  period?: string;
  key_facts?: string[];
  born_year?: string;
  death_year?: string;
  nationality?: string;
  texts?: Text[];
};

export type Text = {
  id?: number;
  title: string;
  content: string;
  author?: Author;
};
