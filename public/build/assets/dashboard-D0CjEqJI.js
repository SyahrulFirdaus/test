b();g();function b(){const t=document.querySelector("[data-sidebar-toggle]"),r=document.querySelector("[data-sidebar]"),a=document.querySelector("[data-sidebar-backdrop]");if(!t||!r)return;const i=s=>{r.classList.toggle("-translate-x-full",!s),a==null||a.classList.toggle("hidden",!s),t.setAttribute("aria-expanded",s?"true":"false")};t.addEventListener("click",()=>{i(r.classList.contains("-translate-x-full"))}),a==null||a.addEventListener("click",()=>i(!1)),document.addEventListener("keydown",s=>{s.key==="Escape"&&i(!1)})}function g(){const t=document.querySelector("[data-notification-bell]");if(!t)return;const r=t.querySelector("[data-bell-toggle]"),a=t.querySelector("[data-bell-panel]"),i=t.querySelector("[data-bell-list]"),s=t.querySelector("[data-bell-badge]"),c=document.querySelector("[data-notification-toasts]"),p=t.dataset.notificationBell,d=new Set((t.dataset.seen??"").split(",").map(n=>n.trim()).filter(Boolean));r==null||r.addEventListener("click",()=>{a.classList.toggle("hidden")}),document.addEventListener("click",n=>{t.contains(n.target)||a==null||a.classList.add("hidden")});const f=n=>{if(i){if(!n.length){i.innerHTML='<p class="px-4 py-6 text-center text-sm text-ink-400">Tidak ada notifikasi baru.</p>';return}i.innerHTML=n.map(e=>`
                    <a href="${u(e.url??"#")}"
                       class="block border-b border-ink-100 px-4 py-3 transition-colors last:border-0 hover:bg-brand-50/50">
                        <p class="text-sm font-bold text-ink-900">${l(e.title)}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-ink-500">${l(e.message)}</p>
                        <p class="mt-1 text-[0.65rem] text-ink-400">${l(e.created_at??"")}</p>
                    </a>
                `).join("")}},x=n=>{var o;if(!c)return;const e=document.createElement("div");e.className="pointer-events-auto w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-ink-100 bg-white p-4 shadow-card-hover",e.innerHTML=`
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M18 8.5a6 6 0 0 0-12 0c0 5-2 6.5-2 6.5h16s-2-1.5-2-6.5Z" />
                        <path d="M13.7 19a2 2 0 0 1-3.4 0" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-ink-900">${l(n.title)}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-ink-500">${l(n.message)}</p>
                    ${n.url?`<a href="${u(n.url)}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat detail →</a>`:""}
                </div>
                <button type="button" class="rounded-lg p-1 text-ink-300 transition-colors hover:text-ink-600" aria-label="Tutup notifikasi">✕</button>
            </div>
        `,(o=e.querySelector("button"))==null||o.addEventListener("click",()=>e.remove()),c.append(e),setTimeout(()=>e.remove(),12e3)};setInterval(async()=>{try{const n=await fetch(p,{headers:{Accept:"application/json","X-Requested-With":"XMLHttpRequest"}});if(!n.ok)return;const e=await n.json();s&&(s.textContent=e.unread_count,s.classList.toggle("hidden",!e.unread_count)),f(e.notifications??[]),(e.notifications??[]).filter(o=>!d.has(o.id)).forEach(o=>{d.add(o.id),x(o)})}catch{}},2e4)}function l(t){return String(t??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function u(t){return l(t).replace(/"/g,"&quot;")}
