import fs from 'fs/promises'
import { XMLParser } from 'fast-xml-parser'

async function convert() {
    const langs = ['en', 'ua']
    const output = {}

    const parser = new XMLParser({
        ignoreAttributes: false,
        attributeNamePrefix: '',
        cdataPropName: 'cdata',
    })

    for (const lang of langs) {
        const xml = await fs.readFile(`translations/messages+intl-icu.${lang}.xlf`, 'utf-8')
        const json = parser.parse(xml)

        const transUnits = json.xliff.file.body['trans-unit']

        output[lang] = {}

        for (const unit of transUnits) {
            const key = unit.resname || unit.id
            let value = unit.target
            if (typeof value === 'object' && 'cdata' in value) value = value.cdata
            output[lang][key] = value
        }
    }

    try {
        await fs.mkdir('locales')
    } catch (e) {
        if (e.code !== 'EEXIST') throw e
    }

    await fs.writeFile('locales/messages.json', JSON.stringify(output, null, 2))
    console.log('✅ Combined translations saved to locales/messages.json')
}

convert()
