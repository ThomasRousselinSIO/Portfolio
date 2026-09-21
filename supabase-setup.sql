    -- A executer dans Supabase > SQL Editor.
    create table if not exists public.projects (
    id text primary key,
    title text not null,
    description text not null,
    tags jsonb not null default '[]'::jsonb,
    date text not null default '',
    link text not null default '',
    "createdAt" bigint not null,
    files jsonb not null default '[]'::jsonb
    );

    alter table public.projects enable row level security;
    create policy "Public can read projects" on public.projects
    for select using (true);
    create policy "Public can create projects" on public.projects
    for insert with check (true);
    create policy "Public can update projects" on public.projects
    for update using (true) with check (true);
    create policy "Public can delete projects" on public.projects
    for delete using (true);

    insert into storage.buckets (id, name, public)
    values ('project-files', 'project-files', true)
    on conflict (id) do update set public = true;

    create policy "Public can read project files" on storage.objects
    for select using (bucket_id = 'project-files');
    create policy "Public can upload project files" on storage.objects
    for insert with check (bucket_id = 'project-files');
    create policy "Public can update project files" on storage.objects
    for update using (bucket_id = 'project-files');
    create policy "Public can delete project files" on storage.objects
    for delete using (bucket_id = 'project-files');
