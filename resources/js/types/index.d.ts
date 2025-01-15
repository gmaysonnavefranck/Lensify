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

export interface PaginatedResponse<T = unknown> extends ResultsResponse<T> {
  current_page: number;
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: Link[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

export interface ResultsResponse<T> {
  data: T[];
}

export type Author = {
  id: number;
  name: string;
  born_year: string;
  death_year?: string;
  nationality: string;
  famous_works?: string[];
  key_facts?: string[];
  period?: Period[];
  themes?: Theme[];
  genres?: Genre[];
  texts?: Text[];
};

export type Text = {
  id: number;
  title: string;
  content: string;
  author?: Author;
  year?: string;
  language?: string;
  type?: string;
  translations?: Text[];
  translation_for?: number;
  text_notes?: FootNotes[];
};

export type Genre = {
  id: number;
  name: string;
};

export type Period = {
  id: number;
  name: string;
};

export type Theme = {
  id: number;
  name: string;
};
