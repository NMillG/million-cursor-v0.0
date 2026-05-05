export default function App(): JSX.Element {
  return (
    <main className="app">
      <h1>nMillion Frontend Starter</h1>
      <p>React + TypeScript build is ready for cPanel deployment.</p>
      <ul>
        <li>Run <code>npm run dev</code> for local frontend development</li>
        <li>Run <code>npm run build</code> to generate production assets</li>
        <li>Upload <code>public_html</code> contents to cPanel</li>
      </ul>
    </main>
  );
}
