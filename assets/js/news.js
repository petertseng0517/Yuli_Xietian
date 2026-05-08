const NEWS_JSON = 'assets/data/news.json';

async function fetchNews() {
  try {
    // cache-bust so new posts appear immediately
    const res = await fetch(NEWS_JSON + '?v=' + Date.now());
    if (!res.ok) throw new Error('fetch failed');
    const data = await res.json();
    renderNews(data.posts || []);
  } catch {
    document.getElementById('news-list').innerHTML =
      '<li class="news-error">消息暫時無法載入，請稍後再試。</li>';
  }
}

function renderNews(posts) {
  const list = document.getElementById('news-list');
  if (!posts.length) {
    list.innerHTML = '<li class="news-error">目前尚無活動消息。</li>';
    return;
  }
  list.innerHTML = posts
    .sort((a, b) => new Date(b.date) - new Date(a.date))
    .slice(0, 5)
    .map(post => `
      <li class="news-item">
        <time class="news-date" datetime="${post.date}">${formatDate(post.date)}</time>
        <a class="news-title" href="news.html?id=${post.id}">${escHtml(post.title)}</a>
      </li>
    `).join('');
}

function formatDate(iso) {
  const d = new Date(iso + 'T00:00:00');
  return `${d.getFullYear()}/${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getDate()).padStart(2, '0')}`;
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', fetchNews);
