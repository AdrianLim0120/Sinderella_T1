// scripts/trigger-cron.js
const puppeteer = require("puppeteer");

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  const url = process.env.CRON_URL;
  const dryrun = process.env.DRYRUN === "1";
  if (!url) {
    console.error("CRON_URL env is missing");
    process.exit(1);
  }
  const triggerUrl = url + (url.includes("?") ? "&" : "?") + (dryrun ? "dryrun=1" : "");

  const browser = await puppeteer.launch({
    headless: "new",
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  });
  const page = await browser.newPage();
  page.setDefaultNavigationTimeout(120000);

  await page.goto(triggerUrl, { waitUntil: "domcontentloaded" });

  let body = "";
  for (let i = 0; i < 20; i++) {          
    try { await page.waitForNavigation({ waitUntil: "networkidle2", timeout: 1000 }); } catch (_) {}
    body = await page.evaluate(() => document.body?.innerText || "");
    if (body.trim().startsWith("{") || body.includes('"ok":')) break; 
    await sleep(1000);
  }

  console.log("=== CRON OUTPUT START ===");
  console.log(body);
  console.log("=== CRON OUTPUT END ===");

  await browser.close();

  if (!body.trim().startsWith("{")) process.exit(1);
})();
