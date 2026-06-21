--
-- PostgreSQL database dump
--

\restrict 0ztyKRqcd0NZCpIgWP57JxuqKot0qdTtQccOyGyezxZLdESa7UqOD1pGUqZPZAU

-- Dumped from database version 18.3 (Debian 18.3-1.pgdg12+1)
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: grad_5fi9_user
--

COPY public.users (id, name, email, phone, email_verified_at, role_id, department_id, verification_token, status, password, remember_token, created_at, updated_at) FROM stdin;
1	System Admin	admin@system.com	0996644362	\N	5	\N	\N	t	$2y$12$wkbYFJJD3B5Fz46Buwt7/u8YCGas1Dq0FvgfbDDWLEuCej1J8CMzS	\N	2026-06-06 14:19:47	2026-06-06 14:19:47
3	manger	manger@system.com	\N	\N	1	\N	\N	t	$2y$12$wkbYFJJD3B5Fz46Buwt7/u8YCGas1Dq0FvgfbDDWLEuCej1J8CMzS	\N	\N	\N
2	مدير المستودع	warehouse@hospital.com	0888888888	\N	2	\N	\N	t	$2y$12$wkbYFJJD3B5Fz46Buwt7/u8YCGas1Dq0FvgfbDDWLEuCej1J8CMzS	\N	2026-06-06 14:20:08	2026-06-06 14:20:08
5	department head	department@head.com	\N	\N	3	1	\N	t	$2y$12$wkbYFJJD3B5Fz46Buwt7/u8YCGas1Dq0FvgfbDDWLEuCej1J8CMzS	\N	\N	\N
6	لجنة الشراء	purchase@head.com	\N	\N	4	\N	\N	t	$2y$12$wkbYFJJD3B5Fz46Buwt7/u8YCGas1Dq0FvgfbDDWLEuCej1J8CMzS	\N	\N	\N
\.


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: grad_5fi9_user
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- PostgreSQL database dump complete
--

\unrestrict 0ztyKRqcd0NZCpIgWP57JxuqKot0qdTtQccOyGyezxZLdESa7UqOD1pGUqZPZAU

