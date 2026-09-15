/**
 * Penetapan Harga Jual di sisi browser.
 *
 * Rumus di sini sengaja dibuat identik dengan App\Services\SellingPriceEstimator
 * supaya harga yang dilihat pelanggan di Calculator sama persis dengan yang
 * dihitung ulang dan disimpan server saat permintaan penawaran dikirim.
 *
 *   Material                = Jumlah Material x Harga Material
 *   Harga Operasional Mesin = Machine Time x Machine Cost
 *   HPP                     = Material + Operasional Mesin
 *   Risk Cost               = HPP x Risk %
 *   Subtotal                = HPP + Risk Cost + Packaging + Overtime
 *   Profit                  = Subtotal x Profit %
 *   Basic Fee               = tarif menurut sisi terpanjang model
 *   Harga Jual              = Subtotal + Profit + Basic Fee
 *
 * Parameternya dikirim server lewat `config.pricing` — termasuk Machine Cost
 * yang sudah dicocokkan dengan tiap printer — jadi tidak ada aturan Price List
 * yang ditulis ulang di sini.
 */

const round2 = (value) => Math.round(value * 100) / 100;

/**
 * @param {{
 *   technology:string, materialPricePerGram:number, printerKey:string,
 *   quantity:number, totalWeightG:number, minutes:number,
 *   dimensions:{x:number,y:number,z:number}|null,
 *   pricing:object, cost:object
 * }} input
 */
export function sellingPrice(input) {
    const technology = String(input.technology ?? '').toUpperCase();
    const formula = input.pricing?.formulas?.[technology] ?? {};
    const quantity = Math.max(1, Number(input.quantity) || 1);

    // Machine Time sudah mencakup seluruh unit model ini.
    const machineTimeHours = (Number(input.minutes) || 0) / 60;
    const machine = input.pricing?.machines?.[input.printerKey] ?? null;
    const machineCost = machine ? Number(machine.cost) || 0 : Number(formula.machineCost) || 0;

    // Berat model + support berlaku per unit, jadi dikalikan jumlah unit.
    const materialQty = (Number(input.totalWeightG) || 0) * quantity;
    const materialPrice = Number(input.materialPricePerGram) || Number(formula.materialPricePerG) || 0;

    // Satu unit dikemas dalam satu kardus, jadi biayanya ikut jumlah unit.
    const box = smallestFittingBox(input.dimensions, input.pricing?.packaging ?? []);
    const packaging = (box ? box.price : Number(formula.packagingCost) || 0) * quantity;

    // Basic Fee melekat pada objectnya, jadi dikenakan sekali per model.
    const basic = basicFeeFor(input.dimensions, input.cost ?? {});

    const overtime = Number(formula.overtimeCost) || 0;
    const riskPercent = Number(formula.riskPercent) || 0;
    const profitPercent = Number(formula.profitPercent) || 0;

    const machineOperational = machineTimeHours * machineCost;
    const materialCost = materialQty * materialPrice;
    const hpp = machineOperational + materialCost;
    const riskCost = hpp * (riskPercent / 100);
    const subtotal = hpp + riskCost + packaging + overtime;
    const profit = subtotal * (profitPercent / 100);

    return {
        technology,

        machine_time_hours: round2(machineTimeHours),
        machine_cost: round2(machineCost),
        machine_source: machine ? machine.name : null,

        material_qty_g: round2(materialQty),
        material_price_per_g: round2(materialPrice),

        risk_percent: riskPercent,
        profit_percent: profitPercent,
        packaging_source: box ? box.label : null,
        quantity,

        largest_dimension_mm: round2(basic.largestMm),
        basic_fee_label: basic.label,

        material_cost: round2(materialCost),
        machine_operational_cost: round2(machineOperational),
        hpp: round2(hpp),
        risk_cost: round2(riskCost),
        subtotal_hpp_risk: round2(hpp + riskCost),
        packaging: round2(packaging),
        overtime: round2(overtime),
        subtotal: round2(subtotal),
        profit: round2(profit),
        basic_fee: round2(basic.fee),
        selling_price: round2(subtotal + profit + basic.fee),
        total: round2(subtotal + profit + basic.fee),
    };
}

/**
 * Basic Fee menurut sisi terpanjang model, dalam milimeter.
 *
 * Daftar tingkatnya dikirim server dari config yang sama dengan
 * App\Support\BasicFee, jadi urutan dan batasnya tidak ditulis ulang di sini.
 * Dimensi yang masuk sudah terskalakan — diukur dari pivot yang sudah diberi
 * skala — sehingga tidak dikalikan skala lagi.
 */
export function basicFeeFor(dimensions, cost = {}) {
    const tiers = cost.basicFee?.tiers ?? [];
    const largest = Math.max(
        0,
        Number(dimensions?.x ?? 0) || 0,
        Number(dimensions?.y ?? 0) || 0,
        Number(dimensions?.z ?? 0) || 0
    );

    for (const tier of tiers) {
        if (tier.belowMm != null) {
            if (largest < Number(tier.belowMm)) {
                return { largestMm: largest, label: tier.label ?? null, fee: Number(tier.fee) || 0 };
            }

            continue;
        }

        if (tier.upToMm != null) {
            if (largest <= Number(tier.upToMm)) {
                return { largestMm: largest, label: tier.label ?? null, fee: Number(tier.fee) || 0 };
            }

            continue;
        }

        return { largestMm: largest, label: tier.label ?? null, fee: Number(tier.fee) || 0 };
    }

    return { largestMm: largest, label: null, fee: 0 };
}

/**
 * Kardus termurah yang masih memuat model.
 *
 * Daftarnya sudah diurutkan dari yang termurah di server, dan dimensinya sudah
 * terskalakan sejak diukur viewer — keduanya tinggal dibandingkan setelah
 * milimeter diubah ke sentimeter.
 */
function smallestFittingBox(dimensions, boxes) {
    if (!dimensions || !boxes.length) {
        return null;
    }

    const sides = [
        (Number(dimensions.x) || 0) / 10,
        (Number(dimensions.y) || 0) / 10,
        (Number(dimensions.z) || 0) / 10,
    ].sort((a, b) => b - a);

    if (sides[0] <= 0) {
        return null;
    }

    return boxes.find((box) => {
        const inner = box.sides ?? [];

        return inner.length === 3
            && inner[0] >= sides[0]
            && inner[1] >= sides[1]
            && inner[2] >= sides[2];
    }) ?? null;
}
