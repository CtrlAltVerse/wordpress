const https = require('https')
const fs = require('fs').promises

const registerFile = './cav-utilities/classes/Register_Assets.php'
const assets = [
   {
      asset: 'fontawesome',
      repo: 'FortAwesome/Font-Awesome',
      url: `https://use.fontawesome.com/releases/v%VERSION%/css/all.css`,
      cb: updateFontAwesome,
   },
]

function updateFontAwesome(content, { version }) {
   const replace =
      'https://use.fontawesome.com/releases/v%VERSION%/webfonts'.replace(
         '%VERSION%',
         version
      )

   return content.replaceAll('../webfonts', replace)
}

async function getRemoteVersion({ repo }) {
   const req = await fetch(
      `https://api.github.com/repos/${repo}/releases/latest`
   )
   const res = await req.json()

   if (!res?.tag_name) {
      return false
   }

   return res.tag_name
}

async function getLocalVersion({ asset }) {
   const fileContent = await fs.readFile(registerFile, 'utf-8')

   const pattern = new RegExp(
      `wp_register_style\\(\\'${asset}.*(\\d\\.\\d\\.\\d)\\'\\);$`,
      'm'
   )
   const match = fileContent.match(pattern)
   if (null === match) {
      return false
   }

   return match[1]
}

function downloadFile({ version, url }) {
   url = url.replace('%VERSION%', version)

   return new Promise((resolve, reject) => {
      https
         .get(url, (res) => {
            let data = ''

            res.on('data', (chunk) => {
               data += chunk
            })

            res.on('end', () => resolve(data))
         })
         .on('error', () => reject(false))
   })
}

async function updateLocalVersion({ version, asset }) {
   const fileContent = await fs.readFile(registerFile, 'utf-8')

   const pattern = new RegExp(
      `wp_register_style\\(\\'${asset}.*(\\d\\.\\d\\.\\d)\\'\\);$`,
      'm'
   )

   const match = fileContent.match(pattern)

   const updatedContent = fileContent.replace(
      match[0],
      match[0].replace(match[1], version)
   )

   fs.writeFile(registerFile, updatedContent, 'utf-8', () => {
      console.log('Done: ', asset)
   })
}

assets.forEach(async (asset) => {
   const versionRemote = await getRemoteVersion(asset)
   if (!versionRemote) {
      return
   }

   const versionLocal = await getLocalVersion(asset)
   if (!versionLocal) {
      return
   }

   if (versionRemote === versionLocal) {
      console.log(`${asset.asset}: Up to date`)
      return
   }

   asset.version = versionRemote

   let remoteContent = await downloadFile(asset)

   if (!remoteContent) {
      return
   }

   if ('undefined' !== typeof asset?.cb) {
      remoteContent = asset.cb(remoteContent, asset)
   }

   await fs.writeFile(
      `./cav-utilities/assets/${asset.asset}.min.css`,
      remoteContent,
      'utf-8'
   )

   await updateLocalVersion(asset)

   console.log(`${asset.asset}: Updated to ${asset.version}`)
})
