<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog Backend API Documentation</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #172033;
            --muted: #667085;
            --line: #d9e2ec;
            --accent: #0f766e;
            --get: #2563eb;
            --post: #0f766e;
            --patch: #b45309;
            --put: #7c3aed;
            --delete: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        .layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid var(--line);
            background: var(--panel);
            padding: 24px 18px;
        }

        .brand {
            margin-bottom: 22px;
        }

        .brand h1 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
        }

        .brand p,
        .meta {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .nav-group {
            margin-top: 18px;
        }

        .nav-group h2 {
            margin: 0 0 8px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .nav-group a {
            display: block;
            padding: 7px 8px;
            border-radius: 6px;
            color: var(--text);
            font-size: 14px;
            text-decoration: none;
        }

        .nav-group a:hover {
            background: #eef6f5;
            color: var(--accent);
        }

        main {
            padding: 32px;
            max-width: 1100px;
            width: 100%;
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .topbar h2 {
            margin: 0;
            font-size: 30px;
            line-height: 1.15;
        }

        .topbar p {
            margin: 8px 0 0;
            max-width: 720px;
            color: var(--muted);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: var(--panel);
            color: var(--text);
            font-size: 14px;
            font-weight: 650;
            text-decoration: none;
            white-space: nowrap;
        }

        .button:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .section {
            margin-top: 28px;
        }

        .section h3 {
            margin: 0 0 12px;
            font-size: 22px;
        }

        .endpoint {
            margin-bottom: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            overflow: hidden;
        }

        .endpoint summary {
            display: grid;
            grid-template-columns: 82px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            padding: 14px 16px;
            cursor: pointer;
            list-style: none;
        }

        .endpoint summary::-webkit-details-marker {
            display: none;
        }

        .method {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 28px;
            border-radius: 5px;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }

        .method.get { background: var(--get); }
        .method.post { background: var(--post); }
        .method.patch { background: var(--patch); }
        .method.put { background: var(--put); }
        .method.delete { background: var(--delete); }

        .path {
            min-width: 0;
            font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .details {
            border-top: 1px solid var(--line);
            padding: 16px;
        }

        .details p {
            margin: 0 0 12px;
            color: var(--muted);
        }

        .details h4 {
            margin: 16px 0 8px;
            font-size: 14px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table th,
        .table td {
            border-top: 1px solid var(--line);
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
        }

        pre {
            overflow-x: auto;
            margin: 0;
            padding: 12px;
            border-radius: 6px;
            background: #111827;
            color: #d1fae5;
            font-size: 13px;
        }

        code {
            font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace;
        }

        .empty {
            color: var(--muted);
        }

        @media (max-width: 860px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            main {
                padding: 22px;
            }

            .topbar {
                display: block;
            }

            .button {
                margin-top: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <h1>Blog Backend API</h1>
                <p>Laravel API reference generated from the OpenAPI contract.</p>
                <p class="meta" id="version"></p>
            </div>
            <nav id="navigation" aria-label="API sections"></nav>
        </aside>

        <main>
            <div class="topbar">
                <div>
                    <h2 id="title">API Documentation</h2>
                    <p id="description">Loading documentation...</p>
                </div>
                <a class="button" href="{{ $specUrl }}">OpenAPI JSON</a>
            </div>
            <div id="content"></div>
        </main>
    </div>

    <script>
        const specUrl = @json($specUrl);
        const methodOrder = ['get', 'post', 'put', 'patch', 'delete'];

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function schemaExample(schema, components = {}) {
            if (!schema) {
                return null;
            }

            if (schema.example !== undefined) {
                return schema.example;
            }

            if (schema.$ref) {
                const name = schema.$ref.split('/').pop();
                return schemaExample(components.schemas?.[name], components);
            }

            if (schema.allOf) {
                return schema.allOf.reduce((example, item) => {
                    const value = schemaExample(item, components);

                    if (value && typeof value === 'object' && !Array.isArray(value)) {
                        return { ...example, ...value };
                    }

                    return example;
                }, {});
            }

            if (schema.type === 'array') {
                return [schemaExample(schema.items, components)];
            }

            if (schema.type === 'object' || schema.properties) {
                return Object.fromEntries(Object.entries(schema.properties ?? {}).map(([key, value]) => {
                    return [key, schemaExample(value, components)];
                }));
            }

            if (schema.enum) {
                return schema.enum[0];
            }

            if (schema.type === 'integer') {
                return 1;
            }

            if (schema.type === 'boolean') {
                return true;
            }

            if (schema.format === 'date-time') {
                return '2026-07-13T12:00:00Z';
            }

            if (schema.format === 'email') {
                return 'user@example.com';
            }

            return schema.type === 'number' ? 1.0 : 'string';
        }

        function renderParameters(parameters = []) {
            if (!parameters.length) {
                return '';
            }

            const rows = parameters.map((parameter) => `
                <tr>
                    <td><code>${escapeHtml(parameter.name)}</code></td>
                    <td>${escapeHtml(parameter.in)}</td>
                    <td>${parameter.required ? 'yes' : 'no'}</td>
                    <td>${escapeHtml(parameter.description ?? '')}</td>
                </tr>
            `).join('');

            return `
                <h4>Parameters</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>In</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        function renderRequestBody(operation, components) {
            const schema = operation.requestBody?.content?.['application/json']?.schema;

            if (!schema) {
                return '';
            }

            const example = schemaExample(schema, components);

            return `
                <h4>JSON Request</h4>
                <pre><code>${escapeHtml(JSON.stringify(example, null, 2))}</code></pre>
            `;
        }

        function renderResponses(operation) {
            const responses = Object.entries(operation.responses ?? {});

            if (!responses.length) {
                return '';
            }

            const rows = responses.map(([code, response]) => `
                <tr>
                    <td><code>${escapeHtml(code)}</code></td>
                    <td>${escapeHtml(response.description ?? '')}</td>
                </tr>
            `).join('');

            return `
                <h4>Responses</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        function renderOperation(path, method, operation, spec) {
            const summary = operation.summary ? ` - ${operation.summary}` : '';

            return `
                <details class="endpoint">
                    <summary>
                        <span class="method ${method}">${method.toUpperCase()}</span>
                        <span class="path">${escapeHtml(path)}${escapeHtml(summary)}</span>
                    </summary>
                    <div class="details">
                        <p>${escapeHtml(operation.description ?? operation.summary ?? '')}</p>
                        ${operation.security ? '<p><strong>Auth:</strong> Bearer token required.</p>' : ''}
                        ${renderParameters(operation.parameters)}
                        ${renderRequestBody(operation, spec.components ?? {})}
                        ${renderResponses(operation)}
                    </div>
                </details>
            `;
        }

        function render(spec) {
            document.getElementById('title').textContent = spec.info?.title ?? 'API Documentation';
            document.getElementById('description').textContent = spec.info?.description ?? '';
            document.getElementById('version').textContent = `Version ${spec.info?.version ?? '1.0.0'}`;

            const grouped = new Map();

            Object.entries(spec.paths ?? {}).forEach(([path, methods]) => {
                methodOrder.forEach((method) => {
                    const operation = methods[method];

                    if (!operation) {
                        return;
                    }

                    const tag = operation.tags?.[0] ?? 'Other';

                    if (!grouped.has(tag)) {
                        grouped.set(tag, []);
                    }

                    grouped.get(tag).push({ path, method, operation });
                });
            });

            document.getElementById('navigation').innerHTML = Array.from(grouped.keys()).map((tag) => `
                <div class="nav-group">
                    <h2>${escapeHtml(tag)}</h2>
                    <a href="#tag-${escapeHtml(tag.toLowerCase().replace(/[^a-z0-9]+/g, '-'))}">View endpoints</a>
                </div>
            `).join('');

            document.getElementById('content').innerHTML = Array.from(grouped.entries()).map(([tag, operations]) => `
                <section class="section" id="tag-${escapeHtml(tag.toLowerCase().replace(/[^a-z0-9]+/g, '-'))}">
                    <h3>${escapeHtml(tag)}</h3>
                    ${operations.map(({ path, method, operation }) => renderOperation(path, method, operation, spec)).join('')}
                </section>
            `).join('');
        }

        fetch(specUrl)
            .then((response) => response.json())
            .then(render)
            .catch(() => {
                document.getElementById('description').textContent = 'Unable to load the OpenAPI document.';
                document.getElementById('content').innerHTML = '<p class="empty">Check that <code>docs/openapi.json</code> is valid JSON.</p>';
            });
    </script>
</body>
</html>
