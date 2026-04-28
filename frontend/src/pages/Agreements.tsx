import { useState } from "react";
import { FileText, Printer } from "lucide-react";

import Arch from "./Arch";
import Interior from "./Interior";
import ArchInterior from "./ArchInterior";

/* ───────── PRINT CSS ───────── */
const PRINT_CSS = `
@media print {
  @page { size: A4 portrait; margin: 0; }

  html, body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .no-print { display: none !important; }

  .print-area {
    display: block !important;
    width: 100% !important;
    background: #fff !important;
  }

  .page {
    width: 100% !important;
    min-height: 290mm !important;
    margin: 0 !important;
    page-break-after: always;
  }

  .page:last-child {
    page-break-after: auto;
  }
}
`;

function PrintStyles() {
  return <style dangerouslySetInnerHTML={{ __html: PRINT_CSS }} />;
}

export default function Agreements() {
  const [templateType, setTemplateType] = useState("arch_interior");
  const [refId, setRefId] = useState("PR_2026_009/01");
  const [clientName, setClientName] = useState("");
  const [address, setAddress] = useState("");
  const [feeAmount, setFeeAmount] = useState("");
  const [date, setDate] = useState("");
  const [place, setPlace] = useState("");

  const handlePrint = () => window.print();

  const renderTemplate = () => {
    switch (templateType) {
      case "interior_only":
        return <Interior {...{ clientName, refId, address, feeAmount, date, place }} />;
      case "arch_consultation":
        return <Arch {...{ clientName, refId, address, feeAmount, date, place }} />;
      case "arch_interior":
        return <ArchInterior {...{ clientName, refId, address, feeAmount, date, place }} />;
      default:
        return <div>No Template</div>;
    }
  };

  return (
    <>
      <PrintStyles />

      {/* HEADER */}
      <div className="no-print" style={styles.header}>
        <h2>Invoices</h2>
        <div style={{ display: "flex", gap: 10 }}>
          <button style={styles.topBtn}>Accounts</button>
          <button style={{ ...styles.topBtn, color: "red" }}>Log Out</button>
        </div>
      </div>

      {/* MAIN LAYOUT */}
      <div style={styles.container}>

        {/* LEFT SIDEBAR FORM */}
        <div className="no-print" style={styles.sidebar}>
          <h2 style={styles.title}>Proposal Generator</h2>
          <p style={styles.subtitle}>
            Select consultation type and fill details
          </p>

          <div style={styles.form}>

            <div style={styles.group}>
              <label>Consultation Type</label>
              <select style={styles.input}
                value={templateType}
                onChange={(e) => setTemplateType(e.target.value)}
              >
                <option value="interior_only">Interior Design</option>
                <option value="arch_consultation">Arch Consultation</option>
                <option value="arch_interior">Architecture + Interior</option>
              </select>
            </div>

            <div style={styles.group}>
              <label>Date</label>
              <input style={styles.input}
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
              />
            </div>

            <div style={styles.row}>
              <div style={styles.group}>
                <label>Ref Number</label>
                <input style={styles.input}
                  value={refId}
                  onChange={(e) => setRefId(e.target.value)}
                />
              </div>

              <div style={styles.group}>
                <label>Place</label>
                <input style={styles.input}
                  value={place}
                  onChange={(e) => setPlace(e.target.value)}
                />
              </div>
            </div>

            <div style={styles.group}>
              <label>Client Name</label>
              <input style={styles.input}
                value={clientName}
                onChange={(e) => setClientName(e.target.value)}
              />
            </div>

            <div style={styles.group}>
              <label>Project / Address</label>
              <input style={styles.input}
                value={address}
                onChange={(e) => setAddress(e.target.value)}
              />
            </div>

            <div style={styles.group}>
              <label>Professional Fees</label>
              <input style={styles.input}
                type="number"
                value={feeAmount}
                onChange={(e) => setFeeAmount(e.target.value)}
              />
            </div>

          </div>

          <button onClick={handlePrint} style={styles.printBtn}>
            <Printer size={16} /> Print / Save PDF
          </button>
        </div>

        {/* RIGHT PREVIEW */}
        <div className="print-area" style={styles.preview}>
          {renderTemplate()}
        </div>
      </div>
    </>
  );
}

/* ───────── STYLES ───────── */

const styles = {
  header: {
    display: "flex",
    justifyContent: "space-between",
    padding: "14px 20px",
    borderBottom: "1px solid #e5e7eb",
    background: "#fff"
  },

  topBtn: {
    padding: "6px 12px",
    borderRadius: "20px",
    border: "1px solid #cbd5e1",
    background: "#fff",
    cursor: "pointer"
  },

  container: {
    display: "flex",
    gap: "20px",
    padding: "20px",
    // background: "#f1f5f9",
    minHeight: "100vh"
  },

  sidebar: {
    width: "340px",
    background: "#ffffff",
    padding: "20px",
    borderRadius: "12px",
    boxShadow: "0 10px 25px rgba(0,0,0,0.08)"
  },

  title: {
    margin: 0,
    fontSize: "20px",
    fontWeight: "700"
  },

  subtitle: {
    fontSize: "13px",
    color: "#64748b",
    marginBottom: "15px"
  },

  form: {
    display: "flex",
    flexDirection: "column",
    gap: "14px"
  },

  group: {
    display: "flex",
    flexDirection: "column",
    gap: "6px",
    fontSize: "13px"
  },

  row: {
    display: "flex",
    gap: "10px"
  },

  input: {
    padding: "10px",
    borderRadius: "8px",
    border: "1px solid #cbd5e1",
    fontSize: "14px",
    outline: "none"
  },

  printBtn: {
    marginTop: "15px",
    padding: "12px",
    borderRadius: "10px",
    background: "#1e293b",
    color: "#fff",
    border: "none",
    fontWeight: "600",
    cursor: "pointer",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    gap: "8px"
  },

  preview: {
    flex: 1,
  
    padding: "20px",
    borderRadius: "12px",
    overflow: "auto"
  }
};