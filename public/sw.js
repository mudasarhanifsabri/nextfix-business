const CACHE='nextfix-shell-v9';
const CORE=['/offline','/images/pwa/icon-192.png','/images/pwa/icon-512.png','/images/brand/nextfix-square.png'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(CORE)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  if(event.request.method!=='GET')return;
  if(event.request.mode==='navigate'){
    event.respondWith(fetch(event.request,{cache:'no-store'}).catch(()=>caches.match('/offline')));
    return;
  }
  const url=new URL(event.request.url);
  if(url.origin===location.origin&&(url.pathname.startsWith('/build/')||url.pathname.startsWith('/images/')||url.pathname==='/css/themes.css'||url.pathname==='/js/themes.js')){
    event.respondWith(caches.match(event.request).then(cached=>cached||fetch(event.request).then(response=>{const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(event.request,copy));return response;})));
  }
});
self.addEventListener('push',event=>{
  let payload={};
  try{payload=event.data?event.data.json():{};}catch(error){payload={body:event.data?event.data.text():'You have a new NextFix update.'};}
  const notification=payload.notification||payload;
  const data=payload.data||notification.data||{};
  event.waitUntil(self.registration.showNotification(notification.title||'NextFix',{
    body:notification.body||'You have a new update.',
    icon:notification.icon||'/images/pwa/icon-192.png',
    badge:notification.badge||'/images/pwa/icon-192.png',
    tag:notification.tag||data.tag||'nextfix-update',
    renotify:true,
    silent:false,
    vibrate:data.sound==='job'?[260,100,260,100,520]:[180,80,180],
    data:{url:data.url||notification.url||'/'}
  }));
});
self.addEventListener('notificationclick',event=>{
  event.notification.close();
  const target=new URL(event.notification.data?.url||'/',self.location.origin).href;
  event.waitUntil(self.clients.matchAll({type:'window',includeUncontrolled:true}).then(clients=>{
    const existing=clients.find(client=>client.url===target);
    if(existing)return existing.focus();
    return self.clients.openWindow(target);
  }));
});
