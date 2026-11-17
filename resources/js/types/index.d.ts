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
  literary_periods?: Period[];
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
  text_notes?: FootNote[];
  read_offset?: number;
};

export type Book = {
  id: number;
  title: string;
  language?: string | null;
  author?: { id: number; name: string } | null;
  chapters_count?: number;
  last_read?: { id: number; title: string } | null;
};

export type GlossaryType = 'character' | 'place' | 'other';

export type GlossaryEntry = {
  id: number;
  type: GlossaryType;
  name: string;
  aliases: string[] | null;
  description: string | null;
  relation: string | null;
  notes: string | null;
};

export type Chapter = {
  id: number;
  title: string;
  position: number;
  text_notes_count: number;
};

// Where a text sits in its book; null for standalone texts
export type ChapterNav = {
  id: number;
  title: string;
  position: number;
  total: number;
  prev: { id: number; title: string } | null;
  next: { id: number; title: string } | null;
};

export type FootNote = {
  id: number;
  note: string;
  text_content: string;
  start_position: number;
  end_position: number;
  is_ai_generated: boolean;
};

export type VocabularyWord = {
  id: number;
  word: string;
  definition: string | null;
  context: string | null;
  created_at: string;
  text?: { id: number; title: string } | null;
};

export type Quote = {
  id: number;
  content: string;
  created_at: string;
  text?: { id: number; title: string; author?: { id: number; name: string } | null } | null;
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
