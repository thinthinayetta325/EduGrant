 <style>
    /* ================= HEADER ================= */

.top-header{
    background:#fff;
    padding:0 28px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:1px solid var(--border);
    min-height:56px;
}

.header-left h1{
    font-size:22px;
    font-weight:700;
    margin-bottom:3px;
}

.sub{
    font-size:12px;
    color:#64748b;
}

.header-actions{
    display:flex;
    align-items:center;
    gap:18px;
}

/* Language */

.language-switch{
    display:flex;
    align-items:center;
    background:#006D69;
    padding:5px;
    border-radius:8px;
}

.language-switch span{
    color:rgba(255,255,255,.5);
    margin:0 5px;
}

.language-switch a{

    color:rgba(255,255,255,.7);
    text-decoration:none;
    padding:5px 12px;
    border-radius:6px;
    font-size:12px;
    transition:.3s;
}

.language-switch a:hover{

    color:white;
}

.active-lang{

    background:rgba(255,255,255,.2);
    color:white !important;

}

/* Search */

.header-search{

    display:flex;
    align-items:center;
    gap:8px;

    background:#f1f5f9;

    padding:10px 15px;

    border-radius:10px;
}

.header-search input{

    border:none;

    background:none;

    outline:none;

    width:230px;

    font-size:13px;
}

/* Notification */

.notif-btn{

    width:45px;

    height:45px;

    display:flex;

    justify-content:center;

    align-items:center;

    border-radius:12px;

    background:#f1f5f9;

    text-decoration:none;

    color:#333;

    position:relative;

    font-size:20px;

    transition:.3s;
}

.notif-btn:hover{

    background:#e2e8f0;
}

.notif-dot{

    width:9px;

    height:9px;

    background:red;

    border-radius:50%;

    position:absolute;

    top:9px;

    right:9px;

    border:2px solid white;
}

/* Profile */

.profile-link{

    display:flex;

    align-items:center;

    gap:12px;

    text-decoration:none;
}

.profile-image{

    width:45px;

    height:45px;

    border-radius:50%;

    overflow:hidden;

    background:#FFD700;

    display:flex;

    justify-content:center;

    align-items:center;

    font-weight:700;

    color:#004D4A;
}

.profile-image img{

    width:100%;

    height:100%;

    object-fit:cover;
}

.profile-name{

    color:#111827;

    font-weight:700;

    font-size:13px;
}

.profile-role{

    font-size:11px;

    color:#64748b;
}
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --sidebar-bg: #006D69;
            --sidebar-hover: #005a56;
            --accent: #FFD700;
            --accent-hover: #e6c200;
            --accent-light: rgba(255,215,0,0.12);
            --card-bg: #ffffff;
            --body-bg: #f0f7f5;
            --border: #e0eae8;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.06), 0 4px 10px rgba(0,0,0,0.04);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: 0.2s ease;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--body-bg);
            display: flex;
            height: 100vh;
            overflow: hidden;
            overflow-x: hidden;
            color: var(--text-primary);
        }
        .myanmar-font { font-family: 'Padauk', 'Pyidaungsu', sans-serif !important; line-height: 1.4; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
        }
        .sidebar-brand {
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #FFD700, #f59e0b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: #004D4A;
            flex-shrink: 0;
        }
        .brand-text h2 { font-size: 15px; font-weight: 700; letter-spacing: -0.3px; }
        .brand-text p { font-size: 10px; color: #FFD700; font-weight: 500; letter-spacing: 0.3px; }

        .admin-profile {
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .admin-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #FFD700, #f59e0b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #004D4A;
            flex-shrink: 0;
        }
        .admin-meta h4 { font-size: 13px; font-weight: 600; }
        .admin-meta p { font-size: 10px; color: rgba(255,255,255,0.5); font-weight: 400; margin-top: 1px; }

        .sidebar-menu { list-style: none; padding: 12px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { padding: 16px 24px 6px; font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.8px; }
        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 24px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            border-left: 3px solid transparent;
            margin: 2px 8px;
            border-radius: 8px;
        }
        .menu-item a:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .menu-item.active a {
            background: var(--accent-light);
            color: var(--accent);
            border-left-color: var(--accent);
        }
        .menu-item .icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
        .menu-item .badge-count {
            margin-left: auto;
            background: rgba(255,255,255,0.1);
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        .sidebar-footer {
            margin-top: auto;
            padding: 12px 14px 16px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 11px 14px;
            background: linear-gradient(135deg, rgba(239,68,68,0.08), rgba(220,38,38,0.04));
            border: 1px solid rgba(239,68,68,0.12);
            border-radius: 10px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.01em;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(239,68,68,0.06), transparent);
            transition: left 0.5s ease;
        }
        .logout-btn:hover::before {
            left: 100%;
        }
        .logout-icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(239,68,68,0.1);
            flex-shrink: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .logout-text {
            flex: 1;
            transition: color 0.3s ease;
        }
        .logout-arrow {
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateX(-6px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .logout-btn:hover {
            background: linear-gradient(135deg, rgba(239,68,68,0.18), rgba(220,38,38,0.10));
            border-color: rgba(239,68,68,0.25);
            color: #f87171;
            box-shadow: 0 4px 20px rgba(239,68,68,0.12), inset 0 1px 0 rgba(255,255,255,0.04);
            transform: translateY(-1px);
        }
        .logout-btn:hover .logout-icon-wrap {
            background: rgba(239,68,68,0.2);
            box-shadow: 0 0 12px rgba(239,68,68,0.15);
        }
        .logout-btn:hover .logout-arrow {
            opacity: 1;
            transform: translateX(0);
        }
        .logout-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(239,68,68,0.1);
        }

        .workspace { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; overflow-x: hidden; margin-left: 260px; }

        .top-header {
            background: #fff;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            min-height: 56px;
        }
        .top-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; }
        .top-header .sub { font-size: 12px; color: var(--text-secondary); font-weight: 400; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-search {
            display: flex; align-items: center;
            background: var(--body-bg);
            border-radius: 8px;
            padding: 0 12px;
            gap: 8px;
        }
        .header-search input {
            border: none; background: none;
            padding: 8px 0; font-size: 13px;
            outline: none; width: 200px;
            font-family: inherit;
        }
        .header-search input::placeholder { color: var(--text-muted); }
        .notif-btn {
            width: 36px; height: 36px;
            border-radius: 10px;
            border: none;
            background: var(--body-bg);
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: var(--transition);
        }
        .notif-btn:hover { background: #e2e8f0; }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .dashboard-body {
            flex-grow: 1;
            padding: 24px 28px;
            overflow-y: auto;
            overflow-x: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .dashboard-body table { min-width: 600px; }
        .dashboard-body .card { overflow-x: auto; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .stat-card .stat-icon {
            font-size: 22px;
            margin-bottom: 10px;
        }
        .stat-card .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .stat-card .stat-value {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-top: 2px;
        }
        .stat-card .stat-change {
            font-size: 11px;
            font-weight: 500;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-card .stat-change.up { color: var(--accent); }
        .stat-card .stat-change.down { color: #ef4444; }
        .stat-card .stat-glow {
            position: absolute;
            top: -30px;
            right: -30px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: 0.08;
        }
        .stat-card:nth-child(1) .stat-value { color: #006D69; }
        .stat-card:nth-child(1) .stat-glow { background: #006D69; }
        .stat-card:nth-child(2) .stat-value { color: #0d9488; }
        .stat-card:nth-child(2) .stat-glow { background: #0d9488; }
        .stat-card:nth-child(3) .stat-value { color: #f59e0b; }
        .stat-card:nth-child(3) .stat-glow { background: #f59e0b; }
        .stat-card:nth-child(4) .stat-value { color: #10b981; }
        .stat-card:nth-child(4) .stat-glow { background: #10b981; }
        .stat-card:nth-child(5) .stat-value { color: #0891b2; }
        .stat-card:nth-child(5) .stat-glow { background: #0891b2; }
        .stat-card:nth-child(6) .stat-value { color: #004D4A; font-size: 20px; }
        .stat-card:nth-child(6) .stat-glow { background: #FFD700; }

        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .grid-4col { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 20px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-header h3 {
            font-size: 15px;
            font-weight: 600;
        }
        .card-header .card-action {
            font-size: 12px;
            color: #006D69;
            text-decoration: none;
            font-weight: 500;
        }
        .card-header .card-action:hover { color: #003D3B; }
        .card-subtitle {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .admin-table th {
            text-align: left;
            padding: 10px 8px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid var(--border);
        }
        .admin-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .admin-table tr:hover td { background: #f8fafc; }

        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-recommended, .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending, .badge-review { background: #fef3c7; color: #92400e; }
        .badge-submitted { background: #dbeafe; color: #1e40af; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }

        .action-link {
            color: #006D69;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
        }
        .action-link:hover { color: #003D3B; }

        .btn-primary {
            background: #FFD700;
            color: #004D4A;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-family: inherit;
        }
        .btn-primary:hover { background: #e6c200; }
        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border);
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-family: inherit;
        }
        .btn-outline:hover { background: var(--body-bg); }
        .btn-sm { padding: 6px 14px; font-size: 11px; }

        .flex-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .flex-list-item:last-child { border-bottom: none; }
        .flex-list-item .name { font-weight: 600; font-size: 13px; }
        .flex-list-item .meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .chart-bar-group { margin-top: 10px; }
        .chart-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .chart-label {
            width: 90px;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
            text-align: right;
            flex-shrink: 0;
        }
        .chart-track {
            flex-grow: 1;
            height: 22px;
            background: #f1f5f9;
            border-radius: 6px;
            overflow: hidden;
        }
        .chart-fill {
            height: 100%;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding-left: 8px;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
            transition: width 0.8s ease;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .quick-item {
            background: var(--body-bg);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid transparent;
            text-decoration: none;
            color: var(--text-primary);
        }
        .quick-item:hover { border-color: var(--accent); background: var(--accent-light); }

        .bottom-bar {
            background: #fff;
            border-top: 1px solid var(--border);
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-secondary);
            flex-shrink: 0;
        }
        .bottom-links a { color: var(--text-primary); text-decoration: none; margin-left: 18px; font-weight: 500; }

        .recipient-avatars { display: flex; gap: 2px; }
        .recipient-avatars span {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            border: 2px solid #fff;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #006D69 0%, #003D3B 100%);
            border-radius: var(--radius);
            padding: 24px 28px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-banner h2 { font-size: 20px; font-weight: 700; }
        .welcome-banner p { font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 4px; }
        .welcome-banner .btn-primary { background: #FFD700; color: #004D4A; }
        .welcome-banner .btn-primary:hover { background: #fff; color: #006D69; }

        /* Header Include Styles */
        .top-header {
            background: #fff;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            min-height: 56px;
        }
        .top-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; color: var(--text-primary); }
        .top-header .sub { font-size: 12px; color: var(--text-secondary); font-weight: 400; }
        .header-actions { display: flex; align-items: center; gap: 14px; }

        .language-switch {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #006D69 0%, #004D4A 100%);
            border-radius: 8px;
            padding: 3px;
            gap: 2px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 2px 4px rgba(0,109,105,0.2);
        }
        .language-switch a {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
            letter-spacing: 0.3px;
        }
        .language-switch a:hover { color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.1); }
        .language-switch a.active-lang {
            color: #006D69;
            background: #FFD700;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(255,215,0,0.3);
        }
        .language-switch span { color: rgba(255,255,255,0.2); font-size: 12px; }

        .header-search {
            display: flex;
            align-items: center;
            background: var(--body-bg);
            border-radius: var(--radius-sm);
            padding: 0 14px;
            gap: 8px;
            border: 1px solid transparent;
            transition: var(--transition);
            width: 240px;
        }
        .header-search:focus-within {
            border-color: var(--sidebar-bg);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0,109,105,0.1);
            width: 280px;
        }
        .header-search span { color: var(--text-muted); font-size: 14px; }
        .header-search input {
            border: none; background: none;
            padding: 10px 0; font-size: 13px;
            outline: none; width: 100%;
            font-family: inherit; color: var(--text-primary);
        }
        .header-search input::placeholder { color: var(--text-muted); }

        .notif-btn {
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-primary);
        }
        .notif-btn:hover { background: var(--body-bg); border-color: var(--sidebar-bg); transform: translateY(-1px); box-shadow: var(--shadow); }
        .notif-dot {
            position: absolute; top: 8px; right: 8px;
            width: 8px; height: 8px;
            background: #ef4444; border-radius: 50%;
            border: 2px solid #fff;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        .notif-count {
            position: absolute; top: -4px; right: -4px;
            min-width: 18px; height: 18px;
            background: #ef4444; color: #fff;
            font-size: 10px; font-weight: 700;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 5px; border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(239,68,68,0.3);
        }

        .profile-dropdown { position: relative; }
        .profile-link {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 12px 6px 6px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid var(--border);
            border-radius: 40px;
            text-decoration: none;
            transition: var(--transition);
        }
        .profile-link:hover { background: #fff; border-color: var(--sidebar-bg); box-shadow: var(--shadow); }
        .profile-image {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #006D69 0%, #004D4A 100%);
            display: flex; align-items: center; justify-content: center;
            color: #FFD700; font-weight: 700; font-size: 14px;
            overflow: hidden; border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .profile-image img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info { text-align: left; }
        .profile-name { font-size: 13px; font-weight: 600; color: var(--text-primary); line-height: 1.2; }
        .profile-role { font-size: 11px; color: var(--text-secondary); font-weight: 400; }

        .profile-dropdown-menu {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            opacity: 0; visibility: hidden; transform: translateY(-8px);
            transition: var(--transition);
            z-index: 1000; overflow: hidden;
        }
        .profile-dropdown:hover .profile-dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .profile-dropdown-menu a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px; font-size: 13px;
            color: var(--text-primary); text-decoration: none;
            transition: var(--transition);
        }
        .profile-dropdown-menu a:hover { background: var(--body-bg); }
        .profile-dropdown-menu .menu-icon { width: 20px; text-align: center; color: var(--text-secondary); }
        .profile-dropdown-menu hr { border: none; border-top: 1px solid var(--border); margin: 4px 0; }
        .profile-dropdown-menu a.logout-link { color: #dc2626; }
        .profile-dropdown-menu a.logout-link:hover { background: #fef2f2; }

        /* Dark Mode */
        html.dark-mode .top-header { background: rgba(30,41,59,0.8); border-bottom-color: #334155; }
        html.dark-mode .card { background: #1e293b; border-color: #334155; }
        html.dark-mode .card-header h3 { color: #f1f5f9; }
        html.dark-mode .stat-card { background: #1e293b; border-color: #334155; }
        html.dark-mode .welcome-banner { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        html.dark-mode .admin-table td { border-bottom-color: #334155; color: #e2e8f0; }
        html.dark-mode .admin-table tr:hover td { background: rgba(255,255,255,0.03); }
        html.dark-mode .admin-table th { color: #94a3b8; border-bottom-color: #334155; }
        html.dark-mode .flex-list-item { border-bottom-color: #334155; }
        html.dark-mode .chart-track { background: #334155; }
        html.dark-mode .quick-item { background: #1e293b; color: #e2e8f0; }
        html.dark-mode .quick-item:hover { background: #334155; }
        html.dark-mode .bottom-bar { background: #0f172a; border-top-color: #334155; }
        html.dark-mode .bottom-links a { color: #94a3b8; }
        html.dark-mode .notif-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
        html.dark-mode .notif-btn:hover { background: #334155; }
        html.dark-mode .profile-link { background: #334155; border-color: #475569; }
        html.dark-mode .profile-link:hover { background: #475569; }
        html.dark-mode .profile-dropdown-menu { background: #1e293b; border-color: #334155; }
        html.dark-mode .profile-dropdown-menu a:hover { background: #334155; }
        html.dark-mode .profile-dropdown-menu hr { border-top-color: #334155; }
        html.dark-mode .btn-outline { border-color: #475569; color: #94a3b8; }
        html.dark-mode .btn-outline:hover { background: #334155; }
        html.dark-mode input[type="text"], html.dark-mode input[type="email"], html.dark-mode input[type="password"], html.dark-mode select, html.dark-mode textarea {
            background: rgba(255,255,255,0.05); border-color: #475569; color: #f1f5f9;
        }
        html.dark-mode ::placeholder { color: #64748b; }
        html.dark-mode .file-upload { background: #1e293b; border-color: #475569; }
        html.dark-mode .msg-success { background: rgba(16,185,129,0.1); color: #34d399; border-color: rgba(16,185,129,0.2); }
        html.dark-mode .msg-error { background: rgba(239,68,68,0.1); color: #f87171; border-color: rgba(239,68,68,0.2); }
        html.dark-mode .badge-recommended, html.dark-mode .badge-approved { background: rgba(22,163,74,0.15); color: #4ade80; }
        html.dark-mode .badge-pending, html.dark-mode .badge-review { background: rgba(245,158,11,0.15); color: #fbbf24; }
        html.dark-mode .badge-submitted { background: rgba(37,99,235,0.15); color: #60a5fa; }
        html.dark-mode .badge-rejected { background: rgba(220,38,38,0.15); color: #f87171; }
        html.dark-mode .recipient-avatars span { border-color: #1e293b; }
        html.dark-mode .notif-row { border-bottom-color: #334155; }
        html.dark-mode .notif-row:hover { background: rgba(255,255,255,0.03); }
        html.dark-mode .notif-unread { background: rgba(16,185,129,0.08); }
        html.dark-mode .notif-unread:hover { background: rgba(16,185,129,0.12); }

        /* ============ RESPONSIVE ============ */
        /* Sidebar mobile: thin click-strip to close */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; right: 0; bottom: 0;
            width: 0;
            z-index: 90;
        }
        .sidebar-overlay.active { display: block; width: 100%; background: transparent; }

        .hamburger-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px; height: 38px;
            border-radius: 8px;
            background: var(--body-bg);
            border: 1px solid var(--border);
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
            flex-shrink: 0;
        }
        .hamburger-btn:hover { background: var(--border); }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .grid-4col { grid-template-columns: repeat(2, 1fr); }
            .grid-3col { grid-template-columns: repeat(2, 1fr); }
            .grid-2col { grid-template-columns: 1fr; }
            .welcome-banner { flex-direction: column; text-align: center; gap: 16px; }
            .header-search { width: 180px; }
            .header-search:focus-within { width: 220px; }
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -280px !important;
                width: 260px !important;
                height: 100vh !important;
                z-index: 100 !important;
                transition: left 0.3s ease !important;
                overflow-y: auto !important;
            }
            .sidebar.open { left: 0 !important; }

            .hamburger-btn { display: flex; }

            .workspace {
                width: 100%;
                margin-left: 0 !important;
                transition: margin-left 0.3s ease !important;
            }
            body.sidebar-open .workspace {
                margin-left: 0 !important;
            }

            .top-header {
                position: sticky;
                top: 0;
                z-index: 50;
                padding: 0 16px;
                min-height: 56px;
                gap: 8px;
            }
            .top-header h1 { font-size: 14px; }
            .top-header .sub { font-size: 10px; }

            .header-actions { gap: 8px; }
            .header-search { display: none; }
            .language-switch a { padding: 4px 8px; font-size: 10px; }
            .profile-info { display: none; }
            .profile-link { padding: 4px; }
            .profile-link svg { display: none; }

            .dashboard-body {
                padding: 16px;
                gap: 16px;
            }

            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .stat-card { padding: 16px; }
            .stat-card .stat-value { font-size: 20px; }

            .grid-2col,
            .grid-3col,
            .grid-4col { grid-template-columns: 1fr; gap: 16px; }

            .quick-grid { grid-template-columns: 1fr; }

            .bottom-bar {
                flex-direction: column;
                gap: 8px;
                text-align: center;
                padding: 10px 16px;
            }

            .welcome-banner {
                padding: 20px;
                flex-direction: column;
                text-align: center;
            }
            .welcome-banner h2 { font-size: 16px; }

            .card { padding: 16px; }
            .quick-item { padding: 16px; }
            .card-header { flex-wrap: wrap; gap: 8px; }

            .admin-table,
            .admin-table thead,
            .admin-table tbody,
            .admin-table th,
            .admin-table td,
            .admin-table tr {
                display: block;
            }
            .admin-table thead { display: none; }
            .admin-table tr {
                background: var(--card-bg);
                border: 1px solid var(--border);
                border-radius: var(--radius-sm);
                padding: 12px;
                margin-bottom: 12px;
                box-shadow: var(--shadow);
            }
            .admin-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid var(--border);
                text-align: right;
                font-size: 12px;
            }
            .admin-table td:last-child { border-bottom: none; }
            .admin-table td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 11px;
                color: var(--text-secondary);
                text-transform: uppercase;
                letter-spacing: 0.3px;
                text-align: left;
                flex-shrink: 0;
                margin-right: 12px;
            }
            .dashboard-body table { min-width: unset !important; }

            .modal-box {
                width: 95vw !important;
                max-width: 95vw !important;
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-card { padding: 12px; }
            .stat-card .stat-value { font-size: 18px; }
            .stat-card .stat-label { font-size: 10px; }

            .dashboard-body { padding: 12px; gap: 12px; }

            .grid-2col, .grid-3col, .grid-4col { gap: 12px; }
            .card, .quick-item { padding: 12px; }

            .btn-primary, .btn-outline { padding: 7px 12px; font-size: 11px; }

            .header-actions { gap: 6px; }
            .theme-toggle { width: 34px; height: 34px; }
            .notif-btn { width: 34px; height: 34px; }
        }
    </style>