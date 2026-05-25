/**
 * PeriodicTableApp.java
 * Java backend — Element data model, API server, and JSON serialization
 *
 * Usage:
 *   javac PeriodicTableApp.java
 *   java PeriodicTableApp
 *   → Starts HTTP server at http://localhost:8080
 *
 * Endpoints:
 *   GET /api/elements          → All elements JSON
 *   GET /api/elements/{n}      → Single element by atomic number
 *   GET /api/elements/group/{group} → Elements by group
 *   GET /api/elements/search?q={query} → Search by name/symbol
 */

import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpServer;

import java.io.*;
import java.net.InetSocketAddress;
import java.nio.charset.StandardCharsets;
import java.util.*;
import java.util.stream.Collectors;

// ─── Element Model ─────────────────────────────────────────────────────────
class Element {
    int atomicNumber;
    String symbol;
    String name;
    String atomicMass;
    String group;
    int period;
    int column;
    String state;
    String electronConfig;
    String description;

    public Element(int n, String sym, String name, String mass, String group,
                   int period, int col, String state, String config, String desc) {
        this.atomicNumber   = n;
        this.symbol         = sym;
        this.name           = name;
        this.atomicMass     = mass;
        this.group          = group;
        this.period         = period;
        this.column         = col;
        this.state          = state;
        this.electronConfig = config;
        this.description    = desc;
    }

    public String toJSON() {
        return String.format(
            "{\"n\":%d,\"sym\":\"%s\",\"name\":\"%s\",\"mass\":\"%s\"," +
            "\"group\":\"%s\",\"period\":%d,\"col\":%d," +
            "\"state\":\"%s\",\"config\":\"%s\",\"desc\":\"%s\"}",
            atomicNumber, symbol, name, atomicMass,
            group, period, column,
            state, electronConfig.replace("\"", "\\\""),
            description.replace("\"", "\\\"")
        );
    }

    @Override
    public String toString() {
        return String.format("%3d | %-3s | %-18s | %8s | %-15s | Period %d",
            atomicNumber, symbol, name, atomicMass, group, period);
    }
}

// ─── Element Repository ────────────────────────────────────────────────────
class ElementRepository {
    private final List<Element> elements = new ArrayList<>();

    public ElementRepository() {
        loadElements();
    }

    private void loadElements() {
        // First 20 elements (extend to 118 for full table)
        add(1,  "H",  "Hydrogen",    "1.008",  "nonmetal",        1, 1,  "Gas",   "1s¹",         "The lightest element, fundamental to stars and water.");
        add(2,  "He", "Helium",      "4.003",  "noble-gas",       1, 18, "Gas",   "1s²",          "Second lightest element, used in balloons and cryogenics.");
        add(3,  "Li", "Lithium",     "6.941",  "alkali",          2, 1,  "Solid", "[He] 2s¹",     "Soft silver-white metal, essential in rechargeable batteries.");
        add(4,  "Be", "Beryllium",   "9.012",  "alkaline",        2, 2,  "Solid", "[He] 2s²",     "Lightweight metal used in aerospace and nuclear industries.");
        add(5,  "B",  "Boron",       "10.81",  "metalloid",       2, 13, "Solid", "[He] 2s² 2p¹", "Metalloid used in glass, ceramics, and nuclear reactors.");
        add(6,  "C",  "Carbon",      "12.011", "nonmetal",        2, 14, "Solid", "[He] 2s² 2p²", "The basis of all known life, forming millions of compounds.");
        add(7,  "N",  "Nitrogen",    "14.007", "nonmetal",        2, 15, "Gas",   "[He] 2s² 2p³", "Makes up 78% of Earth's atmosphere, essential for life.");
        add(8,  "O",  "Oxygen",      "15.999", "nonmetal",        2, 16, "Gas",   "[He] 2s² 2p⁴", "Essential for combustion and respiration.");
        add(9,  "F",  "Fluorine",    "18.998", "halogen",         2, 17, "Gas",   "[He] 2s² 2p⁵", "Most electronegative element, used in toothpaste.");
        add(10, "Ne", "Neon",        "20.180", "noble-gas",       2, 18, "Gas",   "[He] 2s² 2p⁶", "Famous for glowing orange-red in signs.");
        add(11, "Na", "Sodium",      "22.990", "alkali",          3, 1,  "Solid", "[Ne] 3s¹",     "Highly reactive metal, key component of table salt.");
        add(12, "Mg", "Magnesium",   "24.305", "alkaline",        3, 2,  "Solid", "[Ne] 3s²",     "Lightweight structural metal used in alloys.");
        add(13, "Al", "Aluminum",    "26.982", "post-transition", 3, 13, "Solid", "[Ne] 3s² 3p¹", "Most abundant metal in Earth's crust.");
        add(14, "Si", "Silicon",     "28.086", "metalloid",       3, 14, "Solid", "[Ne] 3s² 3p²", "Foundation of semiconductor technology.");
        add(15, "P",  "Phosphorus",  "30.974", "nonmetal",        3, 15, "Solid", "[Ne] 3s² 3p³", "Essential for DNA, ATP, and bone structure.");
        add(16, "S",  "Sulfur",      "32.06",  "nonmetal",        3, 16, "Solid", "[Ne] 3s² 3p⁴", "Bright yellow solid used in fertilizers and rubber.");
        add(17, "Cl", "Chlorine",    "35.45",  "halogen",         3, 17, "Gas",   "[Ne] 3s² 3p⁵", "Used in water disinfection; toxic gas at high concentrations.");
        add(18, "Ar", "Argon",       "39.948", "noble-gas",       3, 18, "Gas",   "[Ne] 3s² 3p⁶", "Third most abundant gas in Earth's atmosphere.");
        add(19, "K",  "Potassium",   "39.098", "alkali",          4, 1,  "Solid", "[Ar] 4s¹",     "Essential electrolyte for heart and nerve function.");
        add(20, "Ca", "Calcium",     "40.078", "alkaline",        4, 2,  "Solid", "[Ar] 4s²",     "Most abundant metal in the human body; essential for bones.");
        add(26, "Fe", "Iron",        "55.845", "transition",      4, 8,  "Solid", "[Ar] 3d⁶ 4s²","Most abundant element by mass on Earth; cornerstone of steel.");
        add(47, "Ag", "Silver",      "107.87", "transition",      5, 11, "Solid", "[Kr] 4d¹⁰ 5s¹","Best electrical conductor; used in currency and photography.");
        add(79, "Au", "Gold",        "196.97", "transition",      6, 11, "Solid", "[Xe] 4f¹⁴ 5d¹⁰ 6s¹","Most malleable metal; humanity's oldest monetary standard.");
        add(92, "U",  "Uranium",     "238.03", "actinide",        7, 6,  "Solid", "[Rn] 5f³ 6d¹ 7s²","Nuclear fuel; denser than lead.");
    }

    private void add(int n, String sym, String name, String mass, String group,
                     int period, int col, String state, String config, String desc) {
        elements.add(new Element(n, sym, name, mass, group, period, col, state, config, desc));
    }

    public List<Element> getAll() { return Collections.unmodifiableList(elements); }

    public Optional<Element> getByAtomicNumber(int n) {
        return elements.stream().filter(e -> e.atomicNumber == n).findFirst();
    }

    public List<Element> getByGroup(String group) {
        return elements.stream()
            .filter(e -> e.group.equalsIgnoreCase(group))
            .collect(Collectors.toList());
    }

    public List<Element> search(String query) {
        String q = query.toLowerCase();
        return elements.stream()
            .filter(e -> e.name.toLowerCase().contains(q)
                      || e.symbol.toLowerCase().contains(q)
                      || String.valueOf(e.atomicNumber).equals(q))
            .collect(Collectors.toList());
    }

    public Map<String, Long> groupStats() {
        Map<String, Long> stats = new TreeMap<>();
        elements.forEach(e -> stats.merge(e.group, 1L, Long::sum));
        return stats;
    }
}

// ─── JSON Helpers ──────────────────────────────────────────────────────────
class JsonHelper {
    public static String elementsToJSON(List<Element> list) {
        return "[" + list.stream().map(Element::toJSON).collect(Collectors.joining(",")) + "]";
    }

    public static String wrapResponse(boolean success, String key, String value) {
        return String.format("{\"success\":%b,\"%s\":%s}", success, key, value);
    }

    public static String errorResponse(String message) {
        return String.format("{\"success\":false,\"error\":\"%s\"}", message);
    }
}

// ─── HTTP Handler ──────────────────────────────────────────────────────────
class ApiHandler implements HttpHandler {
    private final ElementRepository repo;

    public ApiHandler(ElementRepository repo) {
        this.repo = repo;
    }

    @Override
    public void handle(HttpExchange exchange) throws IOException {
        String method = exchange.getRequestMethod();
        String path   = exchange.getRequestURI().getPath();
        String query  = exchange.getRequestURI().getQuery();

        // CORS headers
        exchange.getResponseHeaders().set("Content-Type", "application/json; charset=UTF-8");
        exchange.getResponseHeaders().set("Access-Control-Allow-Origin", "*");
        exchange.getResponseHeaders().set("Access-Control-Allow-Methods", "GET, OPTIONS");

        if ("OPTIONS".equals(method)) {
            exchange.sendResponseHeaders(204, -1);
            return;
        }

        String responseBody;
        int statusCode = 200;

        try {
            // Route: GET /api/elements
            if (path.equals("/api/elements") && query == null) {
                responseBody = JsonHelper.wrapResponse(true, "elements",
                    JsonHelper.elementsToJSON(repo.getAll()));

            // Route: GET /api/elements/search?q=...
            } else if (path.equals("/api/elements/search")) {
                String q = parseQuery(query, "q");
                List<Element> results = repo.search(q == null ? "" : q);
                responseBody = String.format(
                    "{\"success\":true,\"query\":\"%s\",\"count\":%d,\"results\":%s}",
                    q, results.size(), JsonHelper.elementsToJSON(results));

            // Route: GET /api/elements/stats
            } else if (path.equals("/api/elements/stats")) {
                Map<String, Long> stats = repo.groupStats();
                StringBuilder sb = new StringBuilder("{\"success\":true,\"total\":")
                    .append(repo.getAll().size()).append(",\"byGroup\":{");
                stats.forEach((k, v) -> sb.append("\"").append(k).append("\":").append(v).append(","));
                if (sb.charAt(sb.length() - 1) == ',') sb.deleteCharAt(sb.length() - 1);
                sb.append("}}");
                responseBody = sb.toString();

            // Route: GET /api/elements/group/{group}
            } else if (path.startsWith("/api/elements/group/")) {
                String group = path.substring("/api/elements/group/".length());
                List<Element> group_elements = repo.getByGroup(group);
                responseBody = String.format(
                    "{\"success\":true,\"group\":\"%s\",\"count\":%d,\"elements\":%s}",
                    group, group_elements.size(), JsonHelper.elementsToJSON(group_elements));

            // Route: GET /api/elements/{n}
            } else if (path.startsWith("/api/elements/")) {
                String segment = path.substring("/api/elements/".length());
                try {
                    int n = Integer.parseInt(segment);
                    Optional<Element> el = repo.getByAtomicNumber(n);
                    if (el.isPresent()) {
                        responseBody = JsonHelper.wrapResponse(true, "element", el.get().toJSON());
                    } else {
                        statusCode = 404;
                        responseBody = JsonHelper.errorResponse("Element #" + n + " not found");
                    }
                } catch (NumberFormatException ex) {
                    statusCode = 400;
                    responseBody = JsonHelper.errorResponse("Invalid atomic number: " + segment);
                }

            } else {
                statusCode = 404;
                responseBody = JsonHelper.errorResponse("Endpoint not found: " + path);
            }
        } catch (Exception ex) {
            statusCode = 500;
            responseBody = JsonHelper.errorResponse("Internal server error: " + ex.getMessage());
        }

        byte[] bytes = responseBody.getBytes(StandardCharsets.UTF_8);
        exchange.sendResponseHeaders(statusCode, bytes.length);
        try (OutputStream os = exchange.getResponseBody()) {
            os.write(bytes);
        }
    }

    private String parseQuery(String query, String key) {
        if (query == null) return null;
        for (String part : query.split("&")) {
            String[] kv = part.split("=", 2);
            if (kv.length == 2 && kv[0].equals(key)) return kv[1];
        }
        return null;
    }
}

// ─── Main Application ──────────────────────────────────────────────────────
public class PeriodicTableApp {
    private static final int PORT = 8080;

    public static void main(String[] args) throws Exception {
        ElementRepository repo = new ElementRepository();

        // Console demo
        System.out.println("═══════════════════════════════════════════════════");
        System.out.println("  PERIODIC TABLE — Java Backend");
        System.out.println("═══════════════════════════════════════════════════");
        System.out.printf("  Loaded: %d elements%n%n", repo.getAll().size());

        System.out.println("  FIRST 10 ELEMENTS:");
        repo.getAll().stream().limit(10).forEach(e -> System.out.println("  " + e));

        System.out.println("\n  GROUP STATISTICS:");
        repo.groupStats().forEach((g, c) -> System.out.printf("    %-20s : %d%n", g, c));

        System.out.println("\n  SEARCH ('gold'):");
        repo.search("gold").forEach(e -> System.out.println("  " + e));

        System.out.println("\n═══════════════════════════════════════════════════");

        // Start HTTP server
        HttpServer server = HttpServer.create(new InetSocketAddress(PORT), 0);
        server.createContext("/api", new ApiHandler(repo));
        server.setExecutor(null);
        server.start();

        System.out.println("  HTTP API server running at http://localhost:" + PORT);
        System.out.println("  Endpoints:");
        System.out.println("    GET /api/elements");
        System.out.println("    GET /api/elements/{n}");
        System.out.println("    GET /api/elements/group/{group}");
        System.out.println("    GET /api/elements/search?q={query}");
        System.out.println("    GET /api/elements/stats");
        System.out.println("═══════════════════════════════════════════════════");
    }
}